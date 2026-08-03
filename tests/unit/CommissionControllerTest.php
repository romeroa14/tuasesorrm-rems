<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\CommissionController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Structural tests for the CommissionController.
 *
 * Verifies:
 *  - File exists
 *  - Class extends BaseController
 *  - All expected methods exist and are public
 *
 * @internal
 */
final class CommissionControllerTest extends CIUnitTestCase
{
    public function testControllerFileExists(): void
    {
        $path = APPPATH . 'Controllers/CommissionController.php';
        $this->assertFileExists($path, 'CommissionController.php must exist');
    }

    public function testControllerClassExistsAndExtendsBaseController(): void
    {
        $path = APPPATH . 'Controllers/CommissionController.php';
        $this->assertFileExists($path);
        require_once $path;

        $this->assertTrue(
            class_exists(CommissionController::class),
            'CommissionController class must be defined'
        );

        $reflection = new \ReflectionClass(CommissionController::class);
        $this->assertTrue(
            $reflection->isSubclassOf(\App\Controllers\BaseController::class),
            'CommissionController must extend BaseController'
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function methodProvider(): array
    {
        return [
            // Task 3: Scaffold + Properties
            'index'                    => ['index'],
            'properties'               => ['properties'],
            'propertyForm'             => ['propertyForm'],
            'saveProperty'             => ['saveProperty'],
            'deleteProperty'           => ['deleteProperty'],
            'getPropertyParticipants'  => ['getPropertyParticipants'],
            'saveParticipant'          => ['saveParticipant'],
            'deleteParticipant'        => ['deleteParticipant'],
            // Task 4: Advances
            'advances'                 => ['advances'],
            'advanceForm'              => ['advanceForm'],
            'saveAdvance'              => ['saveAdvance'],
            'deleteAdvance'            => ['deleteAdvance'],
            // Task 5: Settlements + Report
            'settlements'              => ['settlements'],
            'settlementForm'           => ['settlementForm'],
            'saveSettlement'           => ['saveSettlement'],
            'calculateSettlement'      => ['calculateSettlement'],
            'finalizeSettlement'       => ['finalizeSettlement'],
            'settlementDetail'         => ['settlementDetail'],
            'report'                   => ['report'],
        ];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testControllerHasMethod(string $methodName): void
    {
        $path = APPPATH . 'Controllers/CommissionController.php';
        require_once $path;

        $reflection = new \ReflectionClass(CommissionController::class);

        $this->assertTrue(
            $reflection->hasMethod($methodName),
            "CommissionController must have {$methodName}() method"
        );

        $method = $reflection->getMethod($methodName);
        $this->assertTrue(
            $method->isPublic(),
            "{$methodName}() must be public"
        );
    }
}
