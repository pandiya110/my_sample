<?php

namespace CodePi\ItemsCardView\Executables;

use CodePi\ItemsCardView\DataSource\ExportCardViewPdfDs as ExportPdfDs;

class ExportCardViewPdf {

    private $dataSource;

    /**
     * 
     * @param ItemsCardViewDs $objItemsDs
     * @param CardViewTransformers $objCardViewTs
     */
    public function __construct(ExportPdfDs $objExportPdf) {
        $this->dataSource = $objExportPdf;
    }

    /**
     * Execution of Get Card view
     * @param Object of GetItemsCardView $command
     * @return array
     */
    public function execute($command) {

        return $this->dataSource->exportCardViewPdf($command);
    }

}
