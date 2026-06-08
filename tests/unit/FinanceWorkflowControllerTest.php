<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\FinanceWorkflowController;
use CodeIgniter\Test\CIUnitTestCase;

final class FinanceWorkflowControllerTest extends CIUnitTestCase
{
    public function testControllerFileExists(): void
    {
        $path = APPPATH . 'Controllers/FinanceWorkflowController.php';
        $this->assertFileExists($path, 'FinanceWorkflowController.php must exist');
    }

    public function testControllerClassExistsAndExtendsBaseController(): void
    {
        $path = APPPATH . 'Controllers/FinanceWorkflowController.php';
        $this->assertFileExists($path);
        require_once $path;

        $this->assertTrue(
            class_exists(FinanceWorkflowController::class),
            'FinanceWorkflowController class must be defined'
        );

        $reflection = new \ReflectionClass(FinanceWorkflowController::class);
        $this->assertTrue(
            $reflection->isSubclassOf(\App\Controllers\BaseController::class),
            'FinanceWorkflowController must extend BaseController'
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function methodProvider(): array
    {
        return [
            'ingreso' => ['ingreso'],
            'egreso'  => ['egreso'],
            'approve' => ['approve'],
            'reject'  => ['reject'],
        ];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testControllerHasWorkflowMethods(string $methodName): void
    {
        $path = APPPATH . 'Controllers/FinanceWorkflowController.php';
        require_once $path;

        $reflection = new \ReflectionClass(FinanceWorkflowController::class);

        $this->assertTrue(
            $reflection->hasMethod($methodName),
            "FinanceWorkflowController must have {$methodName}() method"
        );

        $method = $reflection->getMethod($methodName);
        $this->assertTrue($method->isPublic(), "{$methodName}() must be public");
    }
}
