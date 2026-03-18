<?php

namespace R301\Vue\Component;

class SelectResultat extends Select {
    public function __construct(
        ?string $description,
        ?string $selectedValue = null
    ) {
        $values = [];
        $resultats = ['VICTOIRE', 'DEFAITE', 'NUL'];
        foreach ($resultats as $resultat) {
            $values[$resultat] = $resultat;
        }

        parent::__construct($values, "resultat", $description, $selectedValue);
    }
}