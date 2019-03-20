<?php

namespace CodePi\ReportView\DataSource\DataSourceInterface;

interface iItemsReportView {

    public function getReportViewData($command);

    public function formatResult($command);
}
