<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PublicCustomerInquiryModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

require_once app_path('Helpers/Constants.php');

class AdminPostInquiryController extends Controller
{

    function apiGetViewAllPostInquiryDetails(Request $request)
    {

        $postInquiries = PublicCustomerInquiryModel::select('id', 'request_title', 'name', 'email_id', 'mobile_no', 'message', 'created_at')
            ->paginate(25)->toArray();

        foreach ($postInquiries['data'] as &$inquiry) {
            // Check if created_at key exists and is not null
            if (isset($inquiry['created_at']) && !is_null($inquiry['created_at'])) {
                // Convert created_at to a Carbon instance
                $createdAt = Carbon::parse($inquiry['created_at']);

                // Format the date and time as "d/m/y h:ia"
                $formattedDateTime = $createdAt->format('d/m/y h:iA');

                // Remove the original created_at field
                unset($inquiry['created_at']);

                // Add the formatted date and time to the inquiry array with a new key
                $inquiry['created_at'] = $formattedDateTime;
            }
        }

        unset($inquiry);

        if ($postInquiries['total'] > 0) {

            $postInquiriesList['post_inquiries_list'] = $postInquiries['data'];
            $postInquiriesList['current_page'] = $postInquiries['current_page'];
            $postInquiriesList['per_page'] = $postInquiries['per_page'];
            $postInquiriesList['total'] = $postInquiries['total'];
            $postInquiriesList['last_page'] = $postInquiries['last_page'];

            $responseArray = [
                "status" => true,
                "message" => "Post Inquiries List Found",
                "data" => $postInquiriesList
            ];

            return response()->json($responseArray);

        } else {

            $responseArray = [
                "status" => false,
                "message" => "Post Inquiries Not Found",
                "data" => []
            ];

            return response()->json($responseArray);
        }
    }

    function apiGetViewAllPostInquiryDetailsSheet(Request $request)
    {

        $rules = [
            'selected_month' => 'required|integer|between:1,12',
            'selected_year' => 'required|after:2023',
        ];

        $errorMessages = [];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            $inquiriesList = PublicCustomerInquiryModel::
                select(
                    'name',
                    'request_title',
                    'email_id',
                    'mobile_no',
                    'message',
                    'created_at'
                )
                ->
                whereYear('created_at', $request->selected_year)
                ->whereMonth('created_at', $request->selected_month)
                ->get();

            if ($inquiriesList->isEmpty()) {

                $responseArray = [
                    "status" => false,
                    "message" => "Post Inquiries List Not Found",
                    "data" => []
                ];

                return response()->json($responseArray);

            } else {

                // creation of excel sheet started

                $headers = array_keys($inquiriesList->first()->toArray());

                array_unshift($headers, 'Index');

                foreach ($headers as &$value) {
                    $value = ucwords(str_replace('_', ' ', $value));
                }
                unset($value);

                // Create a new Spreadsheet object
                $spreadsheet = new Spreadsheet();

                // Get the active sheet
                $sheet = $spreadsheet->getActiveSheet();

                // Add headers from the $headers array to the first row
                $sheet->fromArray([$headers], null, 'A1');

                // Initialize the row index
                $row = 2;
                $index = 1; // New index variable

                // Set styles for header row (make them bold, align center, and yellow background)
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']]
                ]);

                // Loop through the collection to add data to subsequent rows
                foreach ($inquiriesList as $item) {

                    $data = $item->toArray();

                    $data['created_at'] = Carbon::parse($data['created_at'])->format('Y-m-d h:i A');

                    // Get the item's attributes dynamically
                    $rowData = array_values($data);

                    $sheet->setCellValue('A' . $row, $index);

                    // Add item's data to each row
                    $sheet->fromArray([array_values($rowData)], null, 'B' . $row);

                    $row++;
                    $index++;
                }

                // Get the highest row and column index
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Loop through each cell and set 'Number' format for numeric cells
                for ($row = 1; $row <= $highestRow; $row++) {
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $cellValue = $sheet->getCell($col . $row)->getValue();
                        if (is_numeric($cellValue)) {
                            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        }
                    }
                }

                // Set borders for all active cells
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ];

                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray($styleArray);

                // Auto-size columns
                foreach (range('A', 'Z') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                $writer = new Xlsx($spreadsheet);

                $directoryPath = app()->basePath('public/inquiries-sheet');

                if (!is_dir($directoryPath)) {
                    // Directory doesn't exist, create it
                    mkdir($directoryPath, 0755, true); // You can adjust the permission mode (e.g., 0755) as needed
                }

                $date = date('Y-m-d_His');

                // Create the new filename by adding the date before the file extension
                // $file_name = 'Quotations' . '-' . $date;

                $file_name = 'Packaging Hub Inquiries Sheet';

                $sheetName = $file_name . '.xlsx';

                // Save the Sheet to a file
                // $sheetFilePath = app()->basePath('public/inquiries-sheet/') . $sheetName;
                // $writer->save($sheetFilePath);

                // creation of excel sheet ended

                // Create a streamed response
                $response = new StreamedResponse(function () use ($writer) {

                    // Set the appropriate headers for Excel file download
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment;filename="Packaging Hub Inquiries Sheet.xlsx"');

                    // Output the Excel content directly to the output buffer
                    $writer->save('php://output');
                });

                // Return the streamed response
                return $response;

                // $data['count'] = $inquiriesList->count() ?? 0;
                // $data['path'] = asset('public/inquiries-sheet') . DIRECTORY_SEPARATOR . str_replace(' ', '%20', $sheetName);

                // $responseArray = [
                //     "status" => true,
                //     "message" => "Post Inquiries List Found",
                //     "data" => $data
                // ];

                // return response()->json($responseArray);

            }
        }
    }
}
