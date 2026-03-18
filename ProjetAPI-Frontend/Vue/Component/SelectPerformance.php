<?php

namespace R301\Vue\Component;

class SelectPerformance extends Select {

    public function __construct(
            ?string $description,
            ?string $selectedValue = null
    ) {
        $values = [];
        $performances = ['EXCELLENTE', 'BONNE', 'MOYENNE', 'MAUVAISE', 'CATASTROPHIQUE'];
        foreach ($performances as $performance) {
            $values[$performance] = $performance;
        }

        parent::__construct($values, "performance", $description, $selectedValue);
    }
}