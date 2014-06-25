<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Util\Format;
use Icinga\Web\Widget\Chart\InlinePie;
use Icinga\Module\Monitoring\Plugin\Perfdata;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;

class Zend_View_Helper_Perfdata extends Zend_View_Helper_Abstract
{
    public function perfdata($perfdataStr, $compact = false, $float = false)
    {
        $result = '';
        $table = array();
        $pset = array_slice(PerfdataSet::fromString($perfdataStr)->asArray(), 0, ($compact ? 5 : null));
        foreach ($pset as $label => $perfdata) {
            if (!$perfdata->isPercentage() && $perfdata->getMaximumValue() === null) {
                continue;
            }
            $pieChart = $this->createInlinePie($perfdata, $label);
            if ($compact) {
                $pieChart->setTitle(
                    htmlspecialchars($label) /* . ': ' . htmlspecialchars($this->formatPerfdataValue($perfdata) */
                );
                if (! $float) {
                    $result .= $pieChart->render();
                } else {
                    $result .= '<div style="float: right;">' . $pieChart->render() . '</div>';
                }
            } else {
                $pieChart->setTitle(htmlspecialchars($label));
                if (! $perfdata->isPercentage()) {
                    $pieChart->setTooltipFormat('{{label}}: {{formatted}} ({{percent}}%)');
                }
                $pieChart->setStyle('float: left; margin: 0.2em 0.5em 0.2em 0.5em;');
                $table[] = '<tr><th>' . $pieChart->render()
                    . htmlspecialchars($label)
                    . '</th><td> '
                    . htmlspecialchars($this->formatPerfdataValue($perfdata)) .
                    ' </td></tr>';
            }
        }

        // TODO: What if we have both? And should we trust sprintf-style placeholders in perfdata titles?
        if (empty($table)) {
            return $compact ? $result : $perfdataStr;
        } else {
            return '<table class="perfdata">' . implode("\n", $table) . '</table>';
        }
    }

    protected function calculatePieChartData(Perfdata $perfdata)
    {
        $rawValue = $perfdata->getValue();
        $minValue = $perfdata->getMinimumValue() !== null ? $perfdata->getMinimumValue() : 0;
        $maxValue = $perfdata->getMaximumValue();
        $usedValue = ($rawValue - $minValue);
        $unusedValue = ($maxValue - $minValue) - $usedValue;

        $gray = $unusedValue;
        $green = $orange = $red = 0;
        // TODO(#6122): Add proper treshold parsing.
        if ($perfdata->getCriticalTreshold() && $perfdata->getValue() > $perfdata->getCriticalTreshold()) {
            $red = $usedValue;
        } elseif ($perfdata->getWarningTreshold() && $perfdata->getValue() > $perfdata->getWarningTreshold()) {
            $orange = $usedValue;
        } else {
            $green = $usedValue;
        }

        return array($green, $orange, $red, $gray);
    }

    protected function formatPerfdataValue(Perfdata $perfdata)
    {
        if ($perfdata->isBytes()) {
            return Format::bytes($perfdata->getValue());
        } elseif ($perfdata->isSeconds()) {
            return Format::seconds($perfdata->getValue());
        } elseif ($perfdata->isPercentage()) {
            return $perfdata->getValue() . '%';
        }

        return $perfdata->getValue();
    }

    protected function createInlinePie(Perfdata $perfdata, $label = '')
    {
        $pieChart = new InlinePie($this->calculatePieChartData($perfdata));
        $pieChart->setLabels(array($label, $label, $label, ''));
        $pieChart->setHideEmptyLabel();

        //$pieChart->setHeight(32)->setWidth(32);
        if ($perfdata->isBytes()) {
            $pieChart->setTooltipFormat('{{label}}: {{formatted}} ({{percent}}%)');
            $pieChart->setNumberFormat(InlinePie::NUMBER_FORMAT_BYTES);
        } else if ($perfdata->isSeconds()) {
            $pieChart->setTooltipFormat('{{label}}: {{formatted}} ({{percent}}%)');
            $pieChart->setNumberFormat(InlinePie::NUMBER_FORMAT_TIME);
        } else {
            $pieChart->setTooltipFormat('{{label}}: {{formatted}}%');
            $pieChart->setNumberFormat(InlinePie::NUMBER_FORMAT_RATIO);
            $pieChart->setHideEmptyLabel();
        }
        return $pieChart;
    }
}
