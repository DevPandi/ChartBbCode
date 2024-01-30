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

        // parse options and tag
        $optionsString = static::unifyLines($tagOption);
        $chartString = static::unifyLines($tagChildren[0]);
        $chartOptions = static::parseOptions($optionsString, $renderer->getTemplater());
        $chartElements = static::parseChart($chartString, $renderer->getTemplater());

        // no options for min and max.
        if (!isset($chartOptions['startAt'])) {
            $chartOptions['startAt'] = static::findNumber();
        }
        if (!isset($chartOptions['endAt'])) {
            $chartOptions['endAt'] = static::findNumber(false);
        }

        return $renderer->getTemplater()->renderTemplate('public:devpandi_bb_code_tag_chartbar', [
            'chartID' => substr(sha1(rand()), 0, 15),
            'chartOptions' => $chartOptions,
            'chartElements' => $chartElements,
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
                            case 'startAt':
                            case 'endAt':
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
                        }
                    }
                }
            }
        }

        return $options;
    }

    protected static function parseChart(string $chartString, Templater $templater): array
    {
        $elements = [];
        $elementName = '';
        $x = 1;
        $lines = explode("\n", $chartString);
        foreach ($lines as $lineNo => $line) {
            if (str_starts_with($line, 'x:')) {
                $elements['x'] = explode(';', substr($line, 2));
                $x = count($elements['x']);
                continue;
            }

            $rawElement = explode(';', $line);
            foreach ($rawElement as $index => $value) {
                $value = static::trim($value, $templater);
                if ($index === 0) {
                    $elements[$value] = [];
                    $elementName = $value;
                    continue;
                }

                if (empty($elementName)) {
                    break;
                }

                if (str_starts_with($value, 'color:')) {
                    $elements[$elementName]['color'] = static::trim(str_replace('color:', '', $value), $templater);
                    continue;
                }

                if (str_starts_with($value, 'border:')) {
                    $elements[$elementName]['border'] = static::trim(str_replace('border:', '', $value), $templater);
                    continue;
                }

                static::$maxValue = (static::$maxValue === null || static::$maxValue < (int) $value) ? (int) $value : static::$maxValue;
                static::$minValue = (static::$minValue === null || static::$minValue > (int) $value) ? (int) $value : static::$minValue;
                $elements[$elementName]['data'][] = $value;
            }
        }

        return $elements;
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
        if ($start) {
            return static::$minValue - (static::$minValue % static::findModulo(static::$maxValue));
        }

        return static::$maxValue + (static::$maxValue % static::findModulo(static::$maxValue));
    }

    protected static function findModulo(int $number): int
    {
        return match (true) {
            $number <= 50 => 5,
            $number <= 100 => 10,
            $number <= 1000 => 100,
            $number >= 10000 => 1000,
        };
    }
}
