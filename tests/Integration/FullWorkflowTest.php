<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Integration;

use WeprestaAcf\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Extension\Audit\AuditEntry;
use WeprestaAcf\Extension\Audit\AuditLogger;
use WeprestaAcf\Extension\Import\ImportResult;
use WeprestaAcf\Extension\Import\Parser\CsvParser;
use WeprestaAcf\Extension\Jobs\JobDispatcher;
use WeprestaAcf\Extension\Jobs\JobEntry;
use WeprestaAcf\Extension\Notifications\Notification;
use WeprestaAcf\Extension\Rules\Action\SetContextAction;
use WeprestaAcf\Extension\Rules\Condition\CartCondition;
use WeprestaAcf\Extension\Rules\RuleBuilder;
use WeprestaAcf\Extension\Rules\RuleContext;
use WeprestaAcf\Extension\Rules\RuleEngine;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration - workflow complet entre extensions
 */
class FullWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Configuration::reset();
        \Context::reset();
    }

    /**
     * Test: Configuration → Rules → Action
     */
    public function testConfigToRulesWorkflow(): void
    {
        // 1. Configurer un seuil via ConfigurationAdapter
        $config = new ConfigurationAdapter();
        $config->set('FREE_SHIPPING_THRESHOLD', '50');

        // 2. Créer une règle basée sur cette config
        $threshold = (float) $config->get('FREE_SHIPPING_THRESHOLD', '0');

        $rule = RuleBuilder::create('free_shipping')
            ->when(new CartCondition('total', '>=', $threshold))
            ->then(new SetContextAction('free_shipping', true))
            ->build();

        // 3. Évaluer avec un panier de 75€
        $context = RuleContext::with(['cart_total' => 75.0]);

        $engine = new RuleEngine();
        $result = $engine->executeFirst([$rule], $context);

        // 4. Vérifier que la règle a été appliquée
        $this->assertTrue($result);
        $this->assertTrue($context->get('free_shipping'));
    }

    /**
     * Test: Rules avec conditions complexes
     */
    public function testComplexRulesEvaluation(): void
    {
        $vipDiscount = RuleBuilder::create('vip_discount')
            ->when(new CartCondition('total', '>=', 100))
            ->then(new SetContextAction('discount', 10))
            ->priority(10)
            ->build();

        $standardDiscount = RuleBuilder::create('standard_discount')
            ->when(new CartCondition('total', '>=', 50))
            ->then(new SetContextAction('discount', 5))
            ->priority(5)
            ->build();

        $context = RuleContext::with(['cart_total' => 120.0]);
        $engine = new RuleEngine();

        // Exécuter seulement la première règle qui matche (priorité haute)
        $engine->executeFirst([$vipDiscount, $standardDiscount], $context);

        // La règle VIP (priorité 10) devrait être appliquée
        $this->assertEquals(10, $context->get('discount'));
    }

    /**
     * Test: Import workflow complet
     */
    public function testImportWorkflow(): void
    {
        // Créer un fichier CSV temporaire
        $csvContent = "reference;name;price\nPROD001;Product 1;19.99\nPROD002;Product 2;29.99\nPROD003;Product 3;39.99";
        $tempFile = sys_get_temp_dir() . '/test_import_' . uniqid() . '.csv';
        file_put_contents($tempFile, $csvContent);

        try {
            $parser = new CsvParser();
            $rows = $parser->parse($tempFile);

            // Simuler un import
            $result = new ImportResult();

            foreach ($rows as $row) {
                $result->incrementProcessed();

                // Simuler la logique d'import
                if (!empty($row['reference']) && !empty($row['name'])) {
                    $result->incrementCreated();
                } else {
                    $result->incrementSkipped();
                }
            }

            // Vérifier les résultats
            $this->assertEquals(3, $result->getProcessed());
            $this->assertEquals(3, $result->getCreated());
            $this->assertTrue($result->isSuccess());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test: Notification construction
     */
    public function testNotificationConstruction(): void
    {
        // Simuler une commande
        $orderId = 12345;
        $customerEmail = 'customer@example.com';

        // Créer une notification
        $notification = Notification::to(
            $customerEmail,
            'Order Confirmation #' . $orderId,
            'Thank you for your order!',
            [
                'order_id' => $orderId,
                'order_date' => date('Y-m-d H:i:s'),
                'total' => 99.99,
            ]
        );

        $this->assertEquals([$customerEmail], $notification->getRecipients());
        $this->assertStringContainsString((string) $orderId, $notification->getSubject());
        $this->assertEquals($orderId, $notification->getData()['order_id']);
    }

    /**
     * Test: Job entry lifecycle
     */
    public function testJobEntryLifecycle(): void
    {
        // 1. Créer un job
        $entry = JobEntry::createPending(
            'SendOrderConfirmationJob',
            ['order_id' => 12345, 'customer_email' => 'test@example.com']
        );

        $this->assertTrue($entry->isPending());
        $this->assertEquals(0, $entry->getAttempts());

        // 2. Démarrer le traitement
        $entry->markAsProcessing();

        $this->assertTrue($entry->isProcessing());
        $this->assertEquals(1, $entry->getAttempts());

        // 3. Terminer avec succès
        $entry->markAsCompleted();

        $this->assertTrue($entry->isCompleted());
        $this->assertNotNull($entry->getCompletedAt());
    }

    /**
     * Test: Audit entry avec changements
     */
    public function testAuditEntryWithChanges(): void
    {
        $oldData = [
            'name' => 'Old Product Name',
            'price' => 19.99,
            'stock' => 100,
        ];

        $newData = [
            'name' => 'New Product Name',
            'price' => 24.99,
            'stock' => 100, // Pas changé
        ];

        $entry = AuditEntry::createForUpdate('Product', 123, $oldData, $newData);
        $entry = $entry->withUser(1, 'Employee', '127.0.0.1');

        // Vérifier les changements détectés
        $changes = $entry->getChanges();

        $this->assertArrayHasKey('name', $changes);
        $this->assertArrayHasKey('price', $changes);
        $this->assertArrayNotHasKey('stock', $changes);

        $this->assertEquals('Old Product Name', $changes['name']['old']);
        $this->assertEquals('New Product Name', $changes['name']['new']);
    }

    /**
     * Test: Configuration multi-shop simulation
     */
    public function testMultiShopConfiguration(): void
    {
        $config = new ConfigurationAdapter();

        // Configurer pour shop 1
        $config->set('MODULE_ENABLED', true);

        // Vérifier
        $this->assertTrue((bool) $config->get('MODULE_ENABLED'));
    }

    /**
     * Test: Workflow complet - Règle → Job → Notification → Audit
     */
    public function testCompleteWorkflow(): void
    {
        // 1. Évaluer une règle pour déterminer si notification nécessaire
        $context = RuleContext::with(['order_total' => 500.0]);

        $highValueRule = RuleBuilder::create('high_value_order')
            ->when(new CartCondition('total', '>=', 200))
            ->then(new SetContextAction('notify_admin', true))
            ->build();

        $engine = new RuleEngine();
        $engine->executeFirst([$highValueRule], $context);

        // 2. Si règle matche, créer une notification
        if ($context->get('notify_admin')) {
            $notification = Notification::to(
                'admin@shop.com',
                'High Value Order Alert',
                'A high value order of 500€ has been placed.'
            );

            $this->assertEquals(['admin@shop.com'], $notification->getRecipients());
        }

        // 3. Créer un job pour envoyer la notification en async
        $jobEntry = JobEntry::createPending(
            'SendNotificationJob',
            ['notification' => ['to' => 'admin@shop.com', 'subject' => 'High Value Order']]
        );

        $this->assertTrue($jobEntry->isPending());

        // 4. Logger l'action dans l'audit
        $auditEntry = AuditEntry::createForCreate(
            'Order',
            999,
            ['total' => 500.0, 'customer_id' => 123]
        );

        $this->assertEquals(AuditEntry::ACTION_CREATE, $auditEntry->getAction());
        $this->assertEquals('Order', $auditEntry->getEntityType());
    }
}

