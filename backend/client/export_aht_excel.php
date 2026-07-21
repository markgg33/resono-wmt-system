<?php

require_once "../connection_db.php";
require_once "lib/aht_query.php";
require_once "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

//--------------------------------------------------
// GET PARAMETERS
//--------------------------------------------------

$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';

$workModeId = $_GET['work_mode_id'] ?? '';

$userId = isset($_GET['user_id'])
    ? intval($_GET['user_id'])
    : 0;

$departmentId = isset($_GET['department_id'])
    ? intval($_GET['department_id'])
    : 0;

$taskIdsRaw = $_GET['task_ids'] ?? '';

$taskIds = [];

if (!empty($taskIdsRaw)) {
    $taskIds = explode(",", $taskIdsRaw);
}

$data = getAHTData(

    $conn,

    $startDate,

    $endDate,

    $workModeId,

    $taskIds,

    $departmentId,

    $userId

);

// Exclude tasks from Excel export
$data = array_values(array_filter($data, function ($row) {

    $excludedTasks = [
        'Away - Break',
        'End Shift'
    ];

    return !in_array($row['task_name'], $excludedTasks);
}));

//--------------------------------------------------
// CREATE SPREADSHEET
//--------------------------------------------------

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Average Handling Time");

//--------------------------------------------------
// TITLE
//--------------------------------------------------

$sheet->mergeCells("A1:G1");

$sheet->setCellValue(
    "A1",
    "Average Handling Time Report"
);

$sheet->getStyle("A1")->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 16
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER
    ]
]);

$sheet->setCellValue("A2", "Start Date");
$sheet->setCellValue("B2", $startDate);

$sheet->setCellValue("D2", "End Date");
$sheet->setCellValue("E2", $endDate);

//--------------------------------------------------
// HEADER
//--------------------------------------------------

$row = 4;

$headers = [

    "Employee",

    "Task Description",

    "Production Time",

    "Total Minutes",

    "Total Volume",

    "Standard AHT",

    "Actual AHT"

];

$column = "A";

foreach ($headers as $header) {

    $sheet->setCellValue(
        $column . $row,
        $header
    );

    $column++;
}

$sheet->getStyle("A4:G4")->applyFromArray([

    'font' => [
        'bold' => true,
        'color' => [
            'rgb' => 'FFFFFF'
        ]
    ],

    'fill' => [

        'fillType' => Fill::FILL_SOLID,

        'startColor' => [
            'rgb' => '2E7D32'
        ]

    ],

    'alignment' => [

        'horizontal' => Alignment::HORIZONTAL_CENTER,

        'vertical' => Alignment::VERTICAL_CENTER

    ]

]);

//--------------------------------------------------
// DATA
//--------------------------------------------------

$row++;

$currentEmployee = "";

$employeeStartRow = $row;

foreach ($data as $index => $item) {

    if ($currentEmployee !== $item["employee_name"]) {

        if ($currentEmployee != "") {

            $sheet->mergeCells(
                "A{$employeeStartRow}:A" . ($row - 1)
            );

            $sheet->getStyle(
                "A{$employeeStartRow}:A" . ($row - 1)
            )->getAlignment()->setVertical(
                Alignment::VERTICAL_CENTER
            );
        }

        $employeeStartRow = $row;

        $currentEmployee = $item["employee_name"];

        $sheet->setCellValue(
            "A$row",
            $item["employee_name"]
        );
    }

    $sheet->setCellValue(
        "B$row",
        $item["task_name"]
    );

    $sheet->setCellValue(
        "C$row",
        $item["production_time"]
    );

    $sheet->setCellValue(
        "D$row",
        $item["total_minutes"]
    );

    $sheet->setCellValue(
        "E$row",
        $item["total_volume"]
    );

    $sheet->setCellValue(
        "F$row",
        $item["standard_aht"]
    );

    $sheet->setCellValue(
        "G$row",
        $item["aht"]
    );

    //--------------------------------------------------
    // COLOR AHT
    //--------------------------------------------------

    if (
        $item["actual_aht_value"] >
        $item["standard_aht_value"]
    ) {

        $sheet->getStyle("G$row")
            ->getFont()
            ->getColor()
            ->setRGB("C62828");
    } else {

        $sheet->getStyle("G$row")
            ->getFont()
            ->getColor()
            ->setRGB("2E7D32");
    }

    $row++;
}

//--------------------------------------------------
// FINAL MERGE
//--------------------------------------------------

if (!empty($data)) {

    $sheet->mergeCells(
        "A{$employeeStartRow}:A" . ($row - 1)
    );

    $sheet->getStyle(
        "A{$employeeStartRow}:A" . ($row - 1)
    )->getAlignment()->setVertical(
        Alignment::VERTICAL_CENTER
    );
}

//--------------------------------------------------
// BORDER
//--------------------------------------------------

$sheet->getStyle(
    "A4:G" . ($row - 1)
)->applyFromArray([

    'borders' => [

        'allBorders' => [

            'borderStyle' =>
            Border::BORDER_THIN

        ]

    ]

]);

//--------------------------------------------------
// ALIGNMENT
//--------------------------------------------------

$sheet->getStyle(
    "A4:G" . ($row - 1)
)->getAlignment()->setHorizontal(
    Alignment::HORIZONTAL_CENTER
);

$sheet->getStyle(
    "B5:B" . ($row - 1)
)->getAlignment()->setHorizontal(
    Alignment::HORIZONTAL_LEFT
);

//--------------------------------------------------
// AUTO SIZE
//--------------------------------------------------

foreach (range('A', 'G') as $col) {

    $sheet
        ->getColumnDimension($col)
        ->setAutoSize(true);
}

//--------------------------------------------------
// FREEZE HEADER
//--------------------------------------------------

$sheet->freezePane("A5");

//--------------------------------------------------
// DOWNLOAD
//--------------------------------------------------

$fileName =
    "Average_Handling_Time_" .
    date("Ymd_His") .
    ".xlsx";

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=\"$fileName\""
);

header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);

$writer->save("php://output");

exit;
