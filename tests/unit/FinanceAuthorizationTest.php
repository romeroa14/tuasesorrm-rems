<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FinanceAuthorization;
use CodeIgniter\Test\CIUnitTestCase;

final class FinanceAuthorizationTest extends CIUnitTestCase
{
    public function testPolicyGrantsCatalogManagementToOwnerAndAdmin(): void
    {
        $ownerPolicy = FinanceAuthorization::policyForRole('owner');
        $adminPolicy = FinanceAuthorization::policyForRole('admin');

        $this->assertTrue($ownerPolicy['access']);
        $this->assertTrue($ownerPolicy['catalog.manage']);
        $this->assertTrue($ownerPolicy['members.manage']);

        $this->assertTrue($adminPolicy['access']);
        $this->assertTrue($adminPolicy['catalog.manage']);
        $this->assertFalse($adminPolicy['members.manage']);
    }

    public function testAssistantHasReadAccessButCannotManageCatalogs(): void
    {
        $assistantPolicy = FinanceAuthorization::policyForRole('assistant');

        $this->assertTrue($assistantPolicy['access']);
        $this->assertFalse($assistantPolicy['catalog.manage']);
        $this->assertFalse($assistantPolicy['legacy.write']);
    }

    public function testUnknownRoleHasNoFinanceAccess(): void
    {
        $unknownPolicy = FinanceAuthorization::policyForRole(null);

        $this->assertFalse($unknownPolicy['access']);
        $this->assertFalse($unknownPolicy['catalog.manage']);
        $this->assertFalse($unknownPolicy['legacy.write']);
    }

    public function testRoleAbilityHelperMatchesPolicyMatrix(): void
    {
        $this->assertTrue(FinanceAuthorization::roleCan('owner', 'access'));
        $this->assertTrue(FinanceAuthorization::roleCan('admin', 'catalog.manage'));
        $this->assertTrue(FinanceAuthorization::roleCan('assistant', 'dashboard.view'));
        $this->assertFalse(FinanceAuthorization::roleCan('assistant', 'catalog.manage'));
        $this->assertFalse(FinanceAuthorization::roleCan('admin', 'legacy.write'));
        $this->assertFalse(FinanceAuthorization::roleCan(null, 'access'));
    }

    public function testOwnerAndAdminCanApproveWorkflowMovements(): void
    {
        $this->assertTrue(FinanceAuthorization::roleCan('owner', 'workflow.approve'));
        $this->assertTrue(FinanceAuthorization::roleCan('admin', 'workflow.approve'));
    }

    public function testAssistantCanDraftAndSubmitButCannotApproveWorkflows(): void
    {
        $this->assertTrue(FinanceAuthorization::roleCan('assistant', 'workflow.draft'));
        $this->assertTrue(FinanceAuthorization::roleCan('assistant', 'workflow.submit'));
        $this->assertFalse(FinanceAuthorization::roleCan('assistant', 'workflow.approve'));
    }
}
