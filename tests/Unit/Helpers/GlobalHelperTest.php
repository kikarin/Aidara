<?php

namespace Tests\Unit\Helpers;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GlobalHelperTest extends TestCase
{
    #[Test]
    public function test_set_date_with_current_timestamp()
    {
        // Act
        $result = set_date();

        // Assert
        $this->assertIsString($result);
        // Check if result contains a valid month name in Indonesian
        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $containsMonth = false;
        foreach ($months as $month) {
            if (strpos($result, $month) !== false) {
                $containsMonth = true;
                break;
            }
        }
        $this->assertTrue($containsMonth, "Result should contain a valid month name in Indonesian");
    }

    #[Test]
    public function test_set_date_with_custom_timestamp()
    {
        // Arrange
        $timestamp = strtotime('2024-01-15');

        // Act
        $result = set_date($timestamp);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('Januari', $result);
    }

    #[Test]
    public function test_set_date_with_string_date()
    {
        // Act
        $result = set_date('2024-03-20');

        // Assert
        $this->assertIsString($result);
    }

    #[Test]
    public function test_convert_bulan_january()
    {
        // Act
        $result = ConvertBulan('01');

        // Assert
        $this->assertEquals('Januari', $result);
    }

    #[Test]
    public function test_convert_bulan_december()
    {
        // Act
        $result = ConvertBulan('12');

        // Assert
        $this->assertEquals('Desember', $result);
    }

    #[Test]
    public function test_convert_bulan_invalid()
    {
        // Act
        $result = ConvertBulan('13');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function test_convert_rp_titik()
    {
        // Act
        $result = ConvertRpTitik(1000000);

        // Assert
        $this->assertEquals('Rp. 1.000.000', $result);
    }

    #[Test]
    public function test_convert_rp_titik_small_number()
    {
        // Act
        $result = ConvertRpTitik(500);

        // Assert
        $this->assertEquals('Rp. 500', $result);
    }

    #[Test]
    public function test_random_string_without_numbers()
    {
        // Act
        $result = RandomString(10, false);

        // Assert
        $this->assertEquals(10, strlen($result));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $result);
    }

    #[Test]
    public function test_random_string_with_numbers_only()
    {
        // Act
        $result = RandomString(5, true);

        // Assert
        $this->assertEquals(5, strlen($result));
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $result);
    }

    #[Test]
    public function test_add_months_with_date()
    {
        // Arrange
        $date = '2024-01-15 10:30:45';

        // Act
        $result = addMonthswithdate($date, 1);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('02', $result);
    }

    #[Test]
    public function test_str_slug()
    {
        // Act
        $result = str_slug('Hello World Test');

        // Assert
        $this->assertEquals('hello-world-test', $result);
    }

    #[Test]
    public function test_initial_name_single_word()
    {
        // Act
        $result = InitialName('John');

        // Assert
        $this->assertEquals('J', $result);
    }

    #[Test]
    public function test_initial_name_multiple_words()
    {
        // Act
        $result = InitialName('John Doe');

        // Assert
        $this->assertEquals('JD', $result);
    }

    #[Test]
    public function test_list_per_page()
    {
        // Act
        $result = ListPerPage();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey(12, $result);
        $this->assertArrayHasKey(25, $result);
        $this->assertArrayHasKey(50, $result);
        $this->assertArrayHasKey(100, $result);
    }

    #[Test]
    public function test_list_bulan_belajar()
    {
        // Act
        $result = ListBulanBelajar();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(12, $result);
        $this->assertArrayHasKey('01', $result);
        $this->assertArrayHasKey('12', $result);
    }

    #[Test]
    public function test_ymd()
    {
        // Act
        $result = Ymd();

        // Assert
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);
    }

    #[Test]
    public function test_date_now()
    {
        // Act
        $result = DateNow();

        // Assert
        $this->assertInstanceOf(Carbon::class, $result);
    }

    #[Test]
    public function test_date_for_human_recent()
    {
        // Arrange
        $tanggal = Carbon::now()->subDays(1);

        // Act
        $result = DateForHuman($tanggal);

        // Assert
        $this->assertIsString($result);
    }

    #[Test]
    public function test_date_for_human_old()
    {
        // Arrange
        $tanggal = Carbon::now()->subDays(5);

        // Act
        $result = DateForHuman($tanggal);

        // Assert
        $this->assertIsString($result);
    }

    #[Test]
    public function test_saparator()
    {
        // Act
        $result = saparator(1000000);

        // Assert
        $this->assertEquals('1.000.000', $result);
    }

    #[Test]
    public function test_saparator_decimal()
    {
        // Act
        $result = saparator(1500);

        // Assert
        $this->assertEquals('1.500', $result);
    }

    #[Test]
    public function test_remove_saparator()
    {
        // Act
        $result = removeSaparator('1.000.000');

        // Assert
        $this->assertEquals('1000000', $result);
    }

    #[Test]
    public function test_list_true_false_ya_tidak()
    {
        // Act
        $result = listTrueFalse('Ya Tidak');

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Ya', $result['1']);
        $this->assertEquals('Tidak', $result['0']);
    }

    #[Test]
    public function test_list_true_false_sudah_belum()
    {
        // Act
        $result = listTrueFalse('Sudah Belum');

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Sudah', $result['1']);
        $this->assertEquals('Belum', $result['0']);
    }

    #[Test]
    public function test_list_true_false_aktif_nonaktif()
    {
        // Act
        $result = listTrueFalse('Aktif Nonaktif');

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Aktif', $result['1']);
        $this->assertEquals('Nonaktif', $result['0']);
    }

    #[Test]
    public function test_sisa_hari_future_date()
    {
        // Arrange
        $date = Carbon::now()->addDays(5);

        // Act
        $result = sisaHari($date);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('Hari', $result);
    }

    #[Test]
    public function test_sisa_hari_past_date()
    {
        // Arrange
        $date = Carbon::now()->subDays(5);

        // Act
        $result = sisaHari($date);

        // Assert
        $this->assertEquals('Berakhir', $result);
    }

    #[Test]
    public function test_sisa_jam_menit_future_date()
    {
        // Arrange
        $date = Carbon::now()->addHours(2)->addMinutes(30);

        // Act
        $result = sisaJamMenit($date);

        // Assert
        $this->assertIsString($result);
    }

    #[Test]
    public function test_sisa_jam_menit_past_date()
    {
        // Arrange
        $date = Carbon::now()->subHours(1);

        // Act
        $result = sisaJamMenit($date);

        // Assert
        $this->assertEquals('Berakhir', $result);
    }
}
