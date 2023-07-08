<?php

namespace App\CommonSDK\BomTool;

use App\Models\EPartV2\Part;
use Illuminate\Support\Arr;

class BomTool
{
    /**
     * Make parse_data
     */
    public static function makeParseData($bom, $columnMap)
    {
        $parseData = null;

        foreach ($bom->excel_data as $rowIndex => $row) {
            if (($rowIndex + 1) < $bom->config_data['data_start_row'] ?? 0) {
                continue;
            }

            foreach ($row as $columnIndex => $columnValue) {
                $columnIndex = $columnMap[$columnIndex] ?? $columnIndex;
                $parseData[$rowIndex][$columnIndex] = $columnValue;
            }
        }

        return $parseData;
    }

    /**
     * Make result_data
     */
    public static function makeResultData($bom)
    {
        // 预生成结果集
        $resultData = Arr::map($bom->parse_data, function ($partItem) {
            return [
                'code'              =>  $partItem['code'],
                'manufacturer'      =>  $partItem['manufacturer'] ?? null,
                'quantity'          =>  $partItem['quantity'] ?? null,
                'package'           =>  $partItem['package'] ?? null,
                'match'             =>  [],
            ];
        });

        // code map
        $searchPartCodes = (function() use ($resultData) {
            $result = [];

            foreach ($resultData as $index => $item) {
                $codes = array_diff(explode(' ', $item['code']), [null, ' ', '', '-', '_', '(', ')', '（', '）']);

                $result[] = [
                    'index' =>  $index,
                    'code'  =>  $item['code'],
                    'codes' =>  $codes,
                ];
            }

            return collect($result);
        })();

        // 数据库中的结果
        $matchParts = Part::query()
            ->whereIn('code', $searchPartCodes->pluck('codes')->collapse()->unique())
            ->with('manufacturer')
            ->with('attributes')
            ->get();

        // 把数据库中的结果插入到结果集中
        foreach ($matchParts as $matchPart) {
            $resultIndexes = $searchPartCodes->where(function ($searchPartCode) use ($matchPart) {
                $code = strtoupper($matchPart->code);
                $codes = collect($searchPartCode['codes'])->map(function ($code) {
                    return strtoupper($code);
                })->toArray();

                return in_array($code, $codes);
            })->pluck('index');

            foreach ($resultIndexes as $index) {
                $resultData[$index]['match'] = $matchPart;
                $resultData[$index]['match']['manufacturer_name'] = $matchPart->manufacturer->name ?? null;
                $resultData[$index]['match']['is_RoHS_compliant'] = true;
                $resultData[$index]['match']['package'] =  $matchPart->attributes->where('name', 'Package')->value('value');
                $resultData[$index]['match']['stock_num'] = $matchPart->stock_num;
            }
        }

        return $resultData;
    }
}
