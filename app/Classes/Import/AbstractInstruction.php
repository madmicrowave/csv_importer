<?php

namespace App\Classes\Import;

/**
 * Interface AbstractInstruction
 * @package App\Classes\Import
 */
abstract class AbstractInstruction implements ImportInstruction
{
    /** @var array */
    protected $data;

    /** @var string */
    protected $filePath;

    /**
     * AbstractInstruction constructor.
     * @param array $data
     * @param string $filePath
     */
    public function __construct(array $data, string $filePath)
    {
        $this->data = $data;
        $this->filePath = $filePath;
    }

    /**
     * @return bool
     */
    public function isInstructionValid(): bool
    {
        if (empty($this->data[0])) { // not possible to verify
            return false;
        }

        $columnSchema = array_keys($this->addTableColumns());

        foreach ($this->addRowFields($this->data[0]) as $columnName => $columnData) {
            if (!in_array($columnName, $columnSchema)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function updateByColumns(): array
    {
        return [];
    }
}
