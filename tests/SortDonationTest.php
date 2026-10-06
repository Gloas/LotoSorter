<?php

class SortDonationTest extends LotoTestCase
{
    protected string $_cwd;
    protected string $_dir;


    protected function setUp(): void
    {
        parent::setUp();
        $this->_cwd = getcwd();
        $this->_dir = sys_get_temp_dir() . '/loto_sorter_' . uniqid();
        mkdir($this->_dir);
        copy(__DIR__ . '/../examples/loto_config.exemple.ini', $this->_dir . '/loto_config.ini');
        chdir($this->_dir);
        mt_srand(42);
    }


    protected function tearDown(): void
    {
        chdir($this->_cwd);
        array_map('unlink', glob($this->_dir . '/*'));
        rmdir($this->_dir);
        mt_srand();
    }


    public function testSortsTheExampleSheet(): void
    {
        $input = realpath(__DIR__ . '/../examples/lots_loto_exemple.csv');

        ob_start();
        new SortDonation($input);
        $output = ob_get_clean();

        $this->assertStringContainsString('Nombre de lots triés', $output);
        $this->assertStringNotContainsString('Trop de boucles', $output);
        $this->assertFileExists('sorted_donations.csv');
        $this->assertFileExists('not_sorted_donations.csv');
        $this->assertFileExists('auto_sort_loto_donations.csv');

        $gifts = static::giftsOf($input);
        $sorted = static::giftsOf('sorted_donations.csv');
        $not_sorted = static::giftsOf('not_sorted_donations.csv');

        $this->assertNotEmpty($sorted);
        $this->assertEqualsCanonicalizing($gifts, array_merge($sorted, $not_sorted),
                                          'every gift is either sorted or not sorted, exactly once');
    }


    /** Rows of a CSV that are gifts (they have a value), as comparable strings */
    protected static function giftsOf(string $filename): array
    {
        $rows = array_map(fn($line) => str_getcsv($line, escape: ''), file($filename));
        $gifts = array_filter($rows,
                              fn($row) => 0 < (int) ($row[2] ?? 0) && 'Mise de' != $row[0]);

        return array_values(array_map(fn($row) => implode('|', array_slice($row, 0, 5)), $gifts));
    }
}
