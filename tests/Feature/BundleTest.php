<?php

namespace Krak\SymfonyMessengerRedis\Tests\Feature;

use Krak\SymfonyMessengerRedis\MessengerRedisBundle;
use Krak\SymfonyMessengerRedis\Tests\Feature\Fixtures\KrakRedisMessage;
use Krak\SymfonyMessengerRedis\Tests\Feature\Fixtures\SfRedisMessage;
use Krak\SymfonyMessengerRedis\Transport\RedisTransport;
use Krak\SymfonyMessengerRedis\Transport\RedisTransportFactory;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class BundleTest extends KernelTestCase
{
    use RedisSteps;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /**
         * @var TestKernel $kernel
         */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(MessengerRedisBundle::class);
        $kernel->addTestConfig(__DIR__ . '/Fixtures/redis-config.yaml');
        $kernel->handleOptions($options);

        return $kernel;
    }

    protected function setUp(): void {
        parent::setUp();
//        $this->addCompilerPass(new PublicServicePass('/(Krak.*|messenger.default_serializer)/'));
        $this->given_a_redis_client_is_configured_with_a_fresh_redis_db();
    }

    protected function getBundleClass() {
        return MessengerRedisBundle::class;
    }

    /** @test */
    public function registers_the_redis_transport_factory_as_a_service() {
        $this->bootKernel();
        $container = $this->getContainer();
        $this->assertInstanceOf(RedisTransportFactory::class, $container->get(RedisTransportFactory::class));
    }

    /** @test */
    public function registers_the_redis_message_bus_integration() {
        // Act: dispatch the krak redis message on the bus
        /** @var MessageBusInterface $bus */
        $bus = $this->getContainer()->get('messenger.default_bus');
        $bus->dispatch(new KrakRedisMessage());

        // Assert: verify the message was pushed to krak redis transport
        $transport = $this->createKrakRedisTransport();
        $res = $transport->get();
        $this->assertCount(1, $res);
        $this->assertInstanceOf(KrakRedisMessage::class, $res[0]->getMessage());
    }

    /** @test */
    public function allows_sf_redis_transport() {
        $this->markTestSkipped();

        // Act: dispatch the sf message on the bus
        /** @var MessageBusInterface $bus */
        $bus = $this->getContainer()->get('messenger.default_bus');
        $bus->dispatch(new SfRedisMessage());

        // Assert: verify the message was not pushed to krak redis transport
        $transport = $this->createKrakRedisTransport();
        $this->assertEquals(0, $transport->getMessageCount());
    }

    private function createKrakRedisTransport(): RedisTransport {
        /** @var RedisTransportFactory $transportFactory */
        $transportFactory = $this->getContainer()->get(RedisTransportFactory::class);
        return $transportFactory->createTransport(getenv('REDIS_DSN'), [
            'blocking_timeout' => 1,
        ], $this->getContainer()->get('messenger.default_serializer'));
    }
}
