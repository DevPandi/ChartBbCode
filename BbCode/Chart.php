<?php

namespace DevPandi\ChartBbCode\BbCode;

class Chart
{
    public static function renderTagBar($tagChildren, $tagOption, $tag, array $options, \XF\BbCode\Renderer\AbstractRenderer $renderer)
    {
        $chartID = substr(sha1(rand()), 0, 10);
        $rawString = $tagChildren[0];
        if (!mb_strlen($rawString > 0)) {
            return '';
        }
        $title = '';
        $parts = array();
        $partCount = 1;
        $elements = array();
        $maxValue = 0;

        $rawString = str_replace(["\r\n", "\r"], "\n", $rawString);
        $rawElements = explode("\n", $rawString);
        foreach ($rawElements as $rawElement) {
            if (!empty(trim($rawElement))) {
                list($tag, $data) = explode(":", $rawElement);
                $rawData[trim($tag)] = trim($data);
            }
        }

        $title = $rawData['title'] ?? 'Chart';

        if (isset($rawData['parts'])) {
            $parts = explode(',', $rawData['parts']);
            $partCount = count($parts);
        }

        if (!isset($rawData['elements'])) {
            return 'Missing elemnts for chart';
        } else {
            $rawElements = explode(',', $rawData['elements']);

            foreach ($rawElements as $rawElement) {
                $elements[] = ['name' => trim($rawElement), 'color' => '', 'data' => []];
            }
        }

        if (!isset($rawData['data'])) {
            return 'Missing data';
        } else {
            if ($partCount > 1) {
                preg_match_all('#\[[^\]]+\]#', $rawData['data'], $dataStrings);
                foreach ($dataStrings[0] as $index => $rawValue) {
                    $rawValue = str_replace(['[', ']'], '', $rawValue);
                    $values = explode(',', $rawValue);
                    foreach ($values as $value) {
                        $maxValue = ($value > $maxValue) ? $value : $maxValue;
                        $elements[$index]['data'][] = $value;
                    }
                }
            } else {
                $rawValue = explode(',', $rawData['data']);
                foreach ($rawValue as $index => $value) {
                    $elements[$index]['data'][0] = $value;

                    $maxValue = ($value > $maxValue) ? $value : $maxValue;
                }
            }
        }

        if (isset($rawData['color'])) {
            if (
                preg_match_all(
                    '#rgb\([0-9]{1,3},[0-9]{1,3},[0-9]{1,3}\)|rgba\([0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0,1].[0-9]\)#',
                    $rawData['color'],
                    $colors
                )
            ) {
                foreach ($colors[0] as $index => $color) {
                    $elements[$index]['color'] = trim($color);
                }
            } else {
                $colors = explode(',', $rawData['color']);
                foreach ($colors as $index => $color) {
                    $elements[$index]['color'] = trim($color);
                }
            }
        }

        $titleY = $rawData['titleY'] ?? '';
        $startAt = $rawData['startAt'] ?? 0;
        $endAt = $rawData['endAt'] ?? static::maxValue($maxValue);

        return $renderer->getTemplater()->renderTemplate('public:devpandi_bb_code_tag_chartbar', [
            'chartID' => $chartID,
            'parts' => $parts,
            'elements' => $elements,
            'titleY' => $titleY,
            'startAt' => $startAt,
            'endAt' => $endAt,
            'title' => $title,
        ]);
    }

    protected static function maxValue($maxValue)
    {
        return ($maxValue < 100) ? $maxValue + (5 - ($maxValue % 5)) : $maxValue + (10 - ($maxValue % 10));
    }
}
