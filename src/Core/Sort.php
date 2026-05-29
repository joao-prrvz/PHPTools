<?php

namespace PHPTools\Core;

class Sort {
    public static function merge(array $array, ?callable $comparer = null, bool $desc = false): array {
        $itemsList = [];
        foreach ($array as $item)
            $itemsList[] = [$item];

        while (count($itemsList) > 1) {
            $itemsListCopy = [...$itemsList];
            $itemsList = [];

            for ($i = 0; $i < count($itemsListCopy) - 1; $i += 2) {
                $currentItems = $itemsListCopy[$i];
                $nextItems = $itemsListCopy[$i + 1];
                $itemsList[] = static::compareLists($currentItems, $nextItems, $comparer, $desc);
            }

            if (count($itemsListCopy) % 2 !== 0)
                $itemsList[] = array_slice($itemsListCopy, -1)[0];
        }

        return $itemsList[0];
    }

    private static function compareLists(array $currentItems, array $nextItems, ?callable $comparer = null, bool $desc = false): array {
        $items = [];

        while (count($currentItems) > 0 && count($nextItems) > 0) {
            $currentItem = $currentItems[0];
            $nextItem = $nextItems[0];
            $condition = static::compareItems($currentItem, $nextItem, $comparer) <= 0;
            if ($condition != $desc) {
                array_splice($currentItems, 0, 1);
                $items[] = $currentItem;
            }
            else {
                array_splice($nextItems, 0, 1);
                $items[] = $nextItem;
            }
            
        }

        $items = array_merge($items, $currentItems);
        $items = array_merge($items, $nextItems);

        return $items;
    }

    private static function compareItems(mixed $a, mixed $b, ?callable $comparer = null): int {
        if ($comparer !== null) {
            $a = $comparer($a);
            $b = $comparer($b);
        }
        return match (get_debug_type($a)) {
            "string" => strcmp($a, $b),
            "int", "float" => $a <=> $b,
            "array" => count($a) <=> count($b),
            default  => -1,
        };
    }
}
