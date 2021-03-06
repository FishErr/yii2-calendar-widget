<?php
/**
 * @link https://github.com/AnatolyRugalev
 * @copyright Copyright (c) AnatolyRugalev
 * @license https://tldrlegal.com/license/gnu-general-public-license-v3-(gpl-3)
 */

namespace understeam\calendar;

use DateTime;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Виджет для отображения календаря
 *
 * @property string $viewMode режим просмотра. Определяется на основе сессии, однако можно задать вручную
 *
 * @author Anatoly Rugalev
 * @link https://github.com/AnatolyRugalev
 */
class CalendarWidget extends Widget
{

    /**
     * @var array сетка моделей для отображения
     */
    public $grid;

    /**
     * @var CalendarInterface компонент календаря
     */
    public $calendar;

    /**
     * @var string View файл заголовка
     */
    public $headerView = '@vendor/understeam/yii2-calendar-widget/src/views/header';

    /**
     * @var string View файл для режима "неделя"
     */
    public $weekView = '@vendor/understeam/yii2-calendar-widget/src/views/week';

    /**
     * @var string View файл для режима "месяц"
     */
    public $monthView = '@vendor/understeam/yii2-calendar-widget/src/views/month';

    /**
     * @var string View файл для ячейки режима "неделя"
     */
    public $weekCellView = '@vendor/understeam/yii2-calendar-widget/src/views/week_cell';

    /**
     * @var string View файл для ячейки режима "месяц"
     */
    public $monthCellView = '@vendor/understeam/yii2-calendar-widget/src/views/month_cell';

    /**
     * @var string устанавливает режим просмотра
     */
    public $viewMode;

    /**
     * @var DatePeriod период времени, который следует отобразить
     */
    public $period;

    /**
     * @var cellDatePeriod формат передаваемых дат
     */
    public $cellDateFormat = 'Y-m-d';

    /**
     * @var string Action, на который будет производиться переход по ссылкам. По умолчанию текущий
     */
    public $action;

    public $actionDateParam = 'date';

    public $actionViewModeParam = 'viewMode';

    public $clientOptions = [];

    public $activeClass = 'active';
    public $futureClass = 'future';
    public $pastClass = 'past';

    public function run()
    {
        $this->registerAssets();
        $result = Html::beginTag('div', [
            'class' => 'calendar-widget',
            'id' => $this->getId()
        ]);
        if ($this->viewMode == CalendarInterface::VIEW_MODE_WEEK) {
            $result .= $this->renderWeek();
        } else {
            $result .= $this->renderMonth();
        }
        $result .= Html::endTag('div');
        return $result;
    }

    public function renderWeek()
    {
        return $this->render($this->weekView, [
            'grid' => $this->grid,
        ]);
    }

    public function renderMonth()
    {
        return $this->render($this->monthView, [
            'grid' => $this->grid,
        ]);
    }

    public function getActionUrl()
    {
        if (!$this->action) {
            return [Yii::$app->controller->getRoute()];
        } else {
            return [$this->action];
        }
    }

    public function getWeekViewUrl()
    {
        $url = $this->getActionUrl();
        $url[$this->actionDateParam] = $this->period->getStartDate()->format('Y-m-d');
        $url[$this->actionViewModeParam] = CalendarInterface::VIEW_MODE_WEEK;
        return $url;
    }

    public function getMonthViewUrl()
    {
        $url = $this->getActionUrl();
        $url[$this->actionDateParam] = $this->period->getEndDate()->sub(new \DateInterval('P1D'))->format('Y-m-d');
        $url[$this->actionViewModeParam] = CalendarInterface::VIEW_MODE_MONTH;
        return $url;
    }

    public function getNextUrl()
    {
        $url = $this->getActionUrl();
        $url[$this->actionDateParam] = $this->getNextDate()->format('Y-m-d');
        $url[$this->actionViewModeParam] = $this->viewMode;
        return $url;
    }

    public function getPrevUrl()
    {
        $url = $this->getActionUrl();
        $url[$this->actionDateParam] = $this->getPrevDate()->format('Y-m-d');
        $url[$this->actionViewModeParam] = $this->viewMode;
        return $url;
    }

    /**
     * @return \DateTime
     */
    public function getNextDate()
    {
        return $this->period->getEndDate();
    }

    /**
     * @return \DateTime
     */
    public function getPrevDate()
    {
        /** @var \DateTime $date */
        $date = $this->period->getStartDate();
        $date->sub(new \DateInterval($this->viewMode == CalendarInterface::VIEW_MODE_WEEK ? 'P7D' : 'P1M'));
        return $date;
    }

    public function getPeriod()
    {
        $firstDay = $this->period->getStartDate();
        $lastDay = $this->period->getEndDate();
        $lastDay->sub(new \DateInterval('P1D'));

        $left = [(int)$firstDay->format('d')];
        $right = [(int)$lastDay->format('d')];
        $common = [];

        if ($firstDay->format('m') == $lastDay->format('m')) {
            $common[] = Yii::$app->formatter->asDate($firstDay, 'MMM');
        } else {
            $left[] = Yii::$app->formatter->asDate($firstDay, 'MMM');
            $right[] = Yii::$app->formatter->asDate($lastDay, 'MMM');
        }

        if ($firstDay->format('Y') == $lastDay->format('Y')) {
            $common[] = Yii::$app->formatter->asDate($firstDay, 'YYYY');
        } else {
            $left[] = Yii::$app->formatter->asDate($firstDay, 'YYYY');
            $right[] = Yii::$app->formatter->asDate($lastDay, 'YYYY');
        }

		return [
            'left' => $left,
            'right' => $right,
            'common' => $common,
        ];
    }

    public function getPeriodString()
    {
        $arrPeriod = $this->getPeriod();
        $string = implode(' ', $arrPeriod['left']) . ' — ' . implode(' ', $arrPeriod['right']);
        if (count($arrPeriod['common'])) {
            $string .= ' ' . implode(' ', $arrPeriod['common']);
        }
        return $string;
    }

    public function getPrevString()
    {
        $arrPeriod = $this->getPeriod();
        return implode(' ', $arrPeriod['left']);
    }

    public function getNextString()
    {
        $arrPeriod = $this->getPeriod();
        if (count($arrPeriod['common'])) {
            return implode(' ', $arrPeriod['right']) . ' ' . implode(' ', $arrPeriod['common']);
        }
        return implode(' ', $arrPeriod['right']);
    }
	
    protected function registerAssets()
    {
        $id = $this->getId();

        $this->clientOptions['pastClass'] = $this->pastClass;
        $this->clientOptions['activeClass'] = $this->activeClass;
        $this->clientOptions['futureClass'] = $this->futureClass;

        $options = Json::htmlEncode($this->clientOptions);
        $view = $this->getView();
        $view->registerJs("jQuery('#$id').yiiCalendar($options);");
    }

    public function isInPeriod(DateTime $date)
    {
        return $date->getTimestamp() >= $this->period->getStartDate()->getTimestamp()
        && $date->getTimestamp() < $this->period->getEndDate()->getTimestamp();
    }

    public function getAllowedDateRange()
    {
        $bounds = $this->calendar->getAllowedDateRange();
        $bounds[0] = isset($bounds[0]) ? $bounds[0] : null;
        $bounds[1] = isset($bounds[1]) ? $bounds[1] : null;
        return $bounds;
    }

    public function isActive(DateTime $date)
    {
        return !$this->isFuture($date) && !$this->isPast($date);
    }

    public function isFuture(DateTime $date)
    {
        $bounds = $this->getAllowedDateRange();
        return $bounds[1] !== null && $date->getTimestamp() >= $bounds[1];
    }

    public function isPast(DateTime $date)
    {
        $bounds = $this->getAllowedDateRange();
        return $bounds[0] !== null && $date->getTimestamp() < $bounds[0];
    }

    public function getCellOptions(GridCell $cell, $addTime = false)
    {
        $options = [
            'data-cal-date' => Yii::$app->formatter->asDate($cell->date, 'php:' .$this->cellDateFormat),
        ];
        if ($addTime) {
            $options['data-cal-time'] = $cell->date->format('H');
        }
        if (!$this->isInPeriod($cell->date)) {
            Html::addCssClass($options, 'out');
        }
        if ($this->isActive($cell->date)) {
            Html::addCssClass($options, $this->activeClass);
        }
        if ($this->isFuture($cell->date)) {
            Html::addCssClass($options, $this->futureClass);
        }
        if ($this->isPast($cell->date)) {
            Html::addCssClass($options, $this->pastClass);
        }
        return $options;
    }
}
