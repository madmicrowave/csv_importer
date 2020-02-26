<?php

namespace App\Classes\Import\Core;

/**
 * Interface AbstractInstruction
 * @package App\Classes\Import\Core
 */
abstract class AbstractInstruction implements ImportInstruction
{
    /** @var string */
    protected $fileName;

    /** @var string */
    protected $filePath;

    /**
     * AbstractInstruction constructor.
     * @param string $fileName
     * @param string $filePath
     */
    public function __construct(string $fileName, string $filePath)
    {
        $this->fileName = $fileName;
        $this->filePath = $filePath;
    }

    /**
     * @param array $currentRow
     * @return array
     */
    public function addRowColumns(array $currentRow): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function addCommonRowColumns(): array
    {
        return [
            'file_name' => $this->fileName,
            'file_path' => $this->filePath,
        ];
    }

    /**
     * @return array
     */
    public function commonColumnsSchema(): array
    {
        return [
            'file_name' => [
                'type' => 'string',
                'index' => true,
            ],
            'file_path' => [
                'type' => 'string',
            ],
        ];
    }
}
