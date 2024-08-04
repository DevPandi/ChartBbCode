<?php

namespace DevPandi\ChartBbCode\BbCode;

use \XF\BbCode\Renderer\AbstractRenderer;

class Chart
{
    protected static Abstractrenderer $renderer;
    protected static bool $escape = false;
    protected static ?int $minValue = null;
    protected static ?int $maxValue = null;
    protected static array $supportedOptions = [
        'bar' => [
            'title',
            'min',
            'max',
            'y',
            'height',
            'width',
            'useX',
        ],
        'line' => [
            'title',
            'min',
            'max',
            'y',
            'x',
            'height',
            'width'
        ],
        'pie' => [
            'title',
            'height',
            'width'
        ],
    ];

    public static function renderBarTag($tagChildren, $tagOption, $tag, array $options, \XF\BbCode\Renderer\AbstractRenderer $renderer)
    {
        static::$renderer = $renderer;
        return static::renderTag($tagChildren, $tagOption, $tag, $options, 'bar');
    }

    public static function renderLineTag($tagChildren, $tagOption, $tag, array $options, \XF\BbCode\Renderer\AbstractRenderer $renderer)
    {
        static::$renderer = $renderer;
        return static::renderTag($tagChildren, $tagOption, $tag, $options, 'line');
    }

    public static function renderPieTag($tagChildren, $tagOption, $tag, array $options, \XF\BbCode\Renderer\AbstractRenderer $renderer)
    {
        static::$renderer = $renderer;
        return static::renderTag($tagChildren, $tagOption, $tag, $options, 'pie');
    }

    protected static function renderTag($tagChildren, $tagOption, $tag, array $options, $type)
    {
        // set defaults
        static::$minValue = static::$maxValue = null;

        // prepare chart string
        $optionsString = static::unifyLines($tagOption);
        $chartString = static::unifyLines($tagChildren[0]);

        //Build Chart Data
        $chartData = array_merge(
            static::parseOptions($optionsString),
            static::parseData($chartString, $type)
        );
        $chartData['id'] = substr(sha1(rand()), 0, 15);
        $chartData['type'] = $type;

        // no options for min and max.
        if (!isset($chartOptions['min'])) {
            $chartData['min'] = static::findNumber();
        }
        if (!isset($chartOptions['max']) && static::$maxValue) {
            $chartData['max'] = static::findNumber(false);
        }

        return static::$renderer->getTemplater()->renderTemplate('public:devpandi_bb_code_tag_chart', [
            'chartData' => $chartData,
        ]);
    }

    protected static function parseOptions(string $optionsString): array
    {
        $options = [];
        if (!empty($optionsString)) {
            $rawOptions = explode(';', $optionsString);

            foreach ($rawOptions as $optionString) {
                if (strpos($optionString, ':')) {
                    $rawOption = explode(':', $optionString);
                    $tag = static::trim($rawOption[0]) ?? '';
                    $value = static::trim($rawOption[1]) ?? '';

                    if ($tag !== '' && $value !== '') {
                        switch ($tag) {
                            case 'title':
                            case 'min':
                            case 'max':
                                $options[$tag] = $value;
                                break;
                            case 'y':
                                if (str_starts_with($value, '#')) {
                                    $options['y_axis'] = ['tick' => true, 'str' => str_replace('#', '', $value), 'start' => true];
                                } elseif (str_ends_with($value, '#')) {
                                    $options['y_axis'] = ['tick' => true, 'str' => str_replace('#', '', $value), 'start' => false];
                                } else {
                                    $options['y_axis'] = ['tick' => false, 'str' => $value];
                                }
                                break;
                            case 'x':
                                $options['x_axis'] = $value;
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

    protected static function parseData(string $chartString, string $type): array
    {
        $labels = [];
        $elements = [];
        $lines = explode("\n", $chartString);

        if ($type == 'pie') {
            $elements = ['color' => [], 'data' => []];
            foreach ($lines as $line) {
                $rawElement = explode(';', $line);
                foreach ($rawElement as $index => $value) {
                    $value = static::trim($value);
                    if ($index === 0) {
                        $labels[] = $value;
                    } elseif (str_starts_with($value, 'color:')) {
                        $elements['color'][] = static::trim(str_replace('color:', '', $value));
                    } else {
                        $elements['data'][] = $value;
                    }
                }
            }

            return ['labels' => $labels, 'elements' => $elements];
        }

        foreach ($lines as $lineNo => $line) {
            if (str_starts_with($line, 'x:')) {
                $labels = explode(';', substr($line, 2));
                continue;
            }

            $rawElement = explode(';', $line);
            $element = [];
            foreach ($rawElement as $index => $value) {
                $value = static::trim($value);
                if ($index === 0) {
                    $element = [];
                    $element['name'] = $value;
                    continue;
                }

                if (empty($element['name'])) {
                    break;
                }

                if (str_starts_with($value, 'color:')) {
                    $element['color'] = static::trim(str_replace('color:', '', $value));
                    continue;
                }

                if (str_starts_with($value, 'border:')) {
                    $element['border'] = static::trim(str_replace('border:', '', $value));
                    continue;
                }

                if ($value == 'dashed') {
                    $element['dashed'] = true;
                    continue;
                }

                if (str_starts_with($value, 'point:')) {
                    $element['point'] = static::parsePoint(str_replace('point:', '', $value));
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

    protected static function parsePoint(string $value): array
    {
        list($point, $size) = explode(',', $value);
        echo $size;

        $point = match ($point) {
            'cross' => 'cross',
            'crossRot' => 'crossRot',
            'dash' => 'dash',
            'line' => 'line',
            'rect' => 'rect',
            'rectRounded' => 'rectRounded',
            'rectRot' => 'rectRot',
            'star' => 'star',
            'triangle' => 'triangle',
            default => 'circle',
        };

        $size = (int) $size;
        $size = ($size < 10) ? 10 : $size;

        return ['style' => $point, 'size' => $size];
    }

    protected static function unifyLines(string $string): string
    {
        return str_replace(["\r\n", "\r"], "\n", $string);
    }

    protected static function trim(string $string): string
    {
        return static::$renderer->getTemplater()->fnTrim(static::$renderer->getTemplater(), static::$escape, $string);
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
