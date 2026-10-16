<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TrabajadorTest extends TestCase
{
    public function test_validates_correct_ruts()
    {
        $rule = new \App\Rules\RutChile();
        $validRuts = ['19.123.456-1', '19123456-1'];

        foreach ($validRuts as $rut) {
            $validator = \Illuminate\Support\Facades\Validator::make(['rut' => $rut], ['rut' => [$rule]]);
            $this->assertTrue($validator->passes());
        }
    }

    public function test_invalidates_incorrect_ruts()
    {
        $rule = new \App\Rules\RutChile();
        $invalidRuts = ['19.123.456-0', '1234', 'abcdefgh', '1.234.567-9', '191234561'];

        foreach ($invalidRuts as $rut) {
            $validator = \Illuminate\Support\Facades\Validator::make(['rut' => $rut], ['rut' => [$rule]]);
            $this->assertTrue($validator->fails());
        }
    }
}
