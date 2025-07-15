<?php
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

// استقبال البيانات
$exportType = $_POST['export_type'] ?? '';
$reportTitle = $_POST['report_title'] ?? 'تقرير';
$tableData = json_decode($_POST['table_data'] ?? '[]', true);

// تأكد من وجود بيانات
if (!$tableData || !is_array($tableData)) {
    die('❌ لا توجد بيانات صالحة للتصدير.');
}

// تصدير Excel
if ($exportType === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // رؤوس الأعمدة
    $headers = array_keys($tableData[0]);
    $colIndex = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($colIndex . '1', $header);
        $colIndex++;
    }

    // البيانات
    $rowNumber = 2;
    foreach ($tableData as $row) {
        $colIndex = 'A';
        foreach ($row as $cell) {
            $sheet->setCellValue($colIndex . $rowNumber, $cell);
            $colIndex++;
        }
        $rowNumber++;
    }

    // حفظ الملف
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$reportTitle.xlsx\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// تصدير PDF
elseif ($exportType === 'pdf') {
    try {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'dejavusans',
            'orientation' => 'L',
            'directionality' => 'rtl'
        ]);

        ob_start();
        ?>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: 'dejavusans';
                    direction: rtl;
                    text-align: right;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 8px;
                }
                th {
                    background: #f0f0f0;
                }
            </style>
        </head>
        <body>
            <h2><?= htmlspecialchars($reportTitle) ?></h2>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($tableData[0]) as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableData as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= htmlspecialchars($cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        $mpdf->WriteHTML($html);
        $mpdf->Output($reportTitle . '.pdf', 'I');
        exit;
    } catch (\Mpdf\MpdfException $e) {
        echo "❌ فشل توليد PDF: " . $e->getMessage();
        exit;
    }
} else {
    echo "❗ نوع التصدير غير معروف.";
}
