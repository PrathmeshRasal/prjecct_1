<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ColumnImport implements ToCollection
{
    // Property to store the transposed data
    private $transposedData = [];

    public function collection(Collection $collection)
    {
        // Transpose the data manually to work with columns
        $this->transposedData = $this->transpose($collection);

        // Process each column
        // foreach ($this->transposedData as $column) {
        //     // $column is an array representing a column of data

        //     // Process each value in the column
        //     foreach ($column as $value) {
        //         // Perform some action with each value
        //         // For example, you might save each value to the database
        //         // or perform some validation
        //         // Your specific logic goes here
        //         echo $value . PHP_EOL;
        //     }
        //     echo '-------------------' . PHP_EOL;

        //     // If you need to do something specific after processing each column
        //     // For example, you might insert data into the database after processing each column
        //     // Your specific logic goes here
        // }
    }

    // Custom transpose method
    private function transpose(Collection $collection)
    {
        $transposed = collect();

        foreach ($collection->toArray() as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                if (!isset($transposed[$colIndex])) {
                    $transposed[$colIndex] = collect();
                }

                $transposed[$colIndex][$rowIndex] = $value;
            }
        }

        return $transposed;
    }

    // Method to retrieve the transposed data
    public function getTransposedData()
    {
        return $this->transposedData;
    }
}
