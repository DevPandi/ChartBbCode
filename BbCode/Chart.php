<?php

namespace DevPandi\ChartBbCode\BbCode;

use XF\Template\Templater;

class Chart
{
    protected static bool $escape = false;
    protected static ?int $minValue = null;
    protected static ?int $maxValue = null;

    public static function renderTagBar($tagChildren, $tagOption, $tag, array $options, \XF\BbCode\Renderer\AbstractRenderer $renderer)
    {
        // reset static value
        static::$minValue = static::$maxValue = null;

        // prepare chart string
        $optionsString = static::unifyLines($tagOption);
        $chartString = static::unifyLines($tagChildren[0]);

        //Build Chart Data
        $chartData = array_merge(
            static::parseOptions($optionsString, $renderer->getTemplater()),
            static::parseChart($chartString, $renderer->getTemplater())
        );
        $chartData['id'] = substr(sha1(rand()), 0, 15);
        $chartData['type'] = 'bar';

        // no options for min and max.
        if (!isset($chartOptions['min'])) {
            $chartData['min'] = static::findNumber();
        }
        if (!isset($chartOptions['max']) && static::$maxValue) {
            $chartData['max'] = static::findNumber(false);
        }

        return $renderer->getTemplater()->renderTemplate('public:devpandi_bb_code_tag_chartbar', [
            'chartData' => $chartData,
        ]);
    }

    protected static function parseOptions(string $optionsString, Templater $templater): array
    {
        $options = [];
        if (!empty($optionsString)) {
            $rawOptions = explode(';', $optionsString);

            foreach ($rawOptions as $optionString) {
                if (strpos($optionString, ':')) {
                    $rawOption = explode(':', $optionString);
                    $tag = static::trim($rawOption[0], $templater) ?? '';
                    $value = static::trim($rawOption[1], $templater) ?? '';

                    if ($tag !== '' && $value !== '') {
                        switch ($tag) {
                            case 'title':
                            case 'min':
                            case 'max':
                                $options[$tag] = $value;
                                break;
                            case 'y':
                                if (str_starts_with($value, '#')) {
                                    $options[$tag] = ['tick' => true, 'str' => str_replace('#', '', $value), 'start' => true];
                                } elseif (str_ends_with($value, '#')) {
                                    $options[$tag] = ['tick' => true, 'str' => str_replace('#', '', $value), 'start' => false];
                                } else {
                                    $options[$tag] = ['tick' => false, 'str' => $value];
                                }
                                break;
                            case 'height':
                            case 'width':
                                $options[$tag] = (int) $value;
                                break;
                            case 'useX':
                                $options[$tag] = (bool) $value;
                                break;
                        }
                    }
                }
            }
        }

        return $options;
    }

    protected static function parseChart(string $chartString, Templater $templater): array
    {
        $labels = [];
        $elements = [];
        $lines = explode("\n", $chartString);
        foreach ($lines as $lineNo => $line) {
            if (str_starts_with($line, 'x:')) {
                $labels = explode(';', substr($line, 2));
                continue;
            }

            $rawElement = explode(';', $line);
            $element = [];
            foreach ($rawElement as $index => $value) {
                $value = static::trim($value, $templater);
                if ($index === 0) {
                    $element = [];
                    $element['name'] = $value;
                    continue;
                }

                if (empty($element['name'])) {
                    break;
                }

                if (str_starts_with($value, 'color:')) {
                    $element['color'] = static::trim(str_replace('color:', '', $value), $templater);
                    continue;
                }

                if (str_starts_with($value, 'border:')) {
                    $element['border'] = static::trim(str_replace('border:', '', $value), $templater);
                    continue;
                }

                static::$maxValue = (static::$maxValue === null || static::$maxValue < (int) $value) ? (int) $value : static::$maxValue;
                static::$minValue = (static::$minValue === null || static::$minValue > (int) $value) ? (int) $value : static::$minValue;
                $element['data'][] = $value;
            }

            if (!empty($element)) {
                $elements[] = $element;
            }
        }


        return ['x' => $labels, 'y' => $elements];
    }

    protected static function unifyLines(string $string): string
    {
        return str_replace(["\r\n", "\r"], "\n", $string);
    }

    protected static function trim(string $string, Templater $templater): string
    {
        return $templater->fnTrim($templater, static::$escape, $string);
    }

    protected static function findNumber(bool $start = true): int
    {
        if (static::$minValue > 0 && static::$maxValue > 1) {
            $modulo = static::findModulo(static::$maxValue);
            if ($start) {
                return static::$minValue - ($modulo + static::$minValue % $modulo);
            }

            return static::$maxValue + (2 * $modulo - (static::$maxValue % $modulo));
        }

        return ($start) ? 0 : 1;
    }

    protected static function findModulo(int $number): int
    {
        $range = (static::$maxValue - static::$minValue);
        $modulo = 10;
        while ($range / 10 >= 10) {
            $range = $range / 10;
            $modulo = $modulo * 10;
        }

        return $modulo;
    }
}
