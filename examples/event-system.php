<?php

declare(strict_types=1);

/**
 * Event System Examples
 *
 * This file demonstrates various usage patterns of the League Container event system.
 */

require_once 'vendor/autoload.php';

use League\Container\Container;
use League\Container\Event\{ServiceResolvedEvent, OnDefineEvent, BeforeResolveEvent};

// Example interfaces and classes
interface LoggerInterface
{
    public function log(string $message): void;
}

class Logger implements LoggerInterface
{
    public function log(string $message): void
    {
        echo "[LOG] $message\n";
    }
}

interface UserInterface
{
    public function getName(): string;
    public function setLogger(LoggerInterface $logger): void;
    public function setCreatedAt(\DateTime $dateTime): void;
}

class User implements UserInterface
{
    private LoggerInterface $logger;
    private \DateTime $createdAt;

    public function __construct(private string $name) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setCreatedAt(\DateTime $dateTime): void
    {
        $this->createdAt = $dateTime;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}

class AdminUser extends User
{
    private bool $isAdmin = false;

    public function setAdmin(bool $admin): void
    {
        $this->isAdmin = $admin;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }
}

echo "=== League Container Event System Examples ===\n\n";

// Create container
$container = new Container();

// Register services
$container->addShared(LoggerInterface::class, Logger::class);
$container->addShared('user', User::class)->addArgument('John Doe');
$container->addShared('admin.user', AdminUser::class)->addArgument('Jane Admin');

echo "1. Basic Event Listening\n";
echo "------------------------\n";

// Listen to service definition events
$container->listen(OnDefineEvent::class, function(OnDefineEvent $event) {
    echo "Service '{$event->getId()}' was defined\n";
});

// Add a new service to trigger the event
$container->add('temp.service', \stdClass::class);

echo "\n2. Object Resolution Events\n";
echo "---------------------------\n";

// Listen to object resolution and inject logger
$container->listen(ServiceResolvedEvent::class, function(ServiceResolvedEvent $event) use ($container) {
    $user = $event->getResolved();
    $logger = $container->get(LoggerInterface::class);
    $user->setLogger($logger);
    $user->setCreatedAt(new \DateTime());
    echo "Injected logger and timestamp into {$event->getId()}\n";
})->forType(UserInterface::class);

// Resolve users to trigger events
$user = $container->get('user');
$adminUser = $container->get('admin.user');

echo "\n3. Tag-Based Filtering\n";
echo "----------------------\n";

// Add services with tags
$container->addShared('service.a', \stdClass::class)->addTag('logging');
$container->addShared('service.b', \stdClass::class)->addTag('logging')->addTag('monitoring');
$container->addShared('service.c', \stdClass::class)->addTag('monitoring');

// Listen for services with 'logging' tag
$container->listen(ServiceResolvedEvent::class, function(ServiceResolvedEvent $event) {
    echo "Service '{$event->getId()}' has logging tag\n";
})->forTag('logging');

// Resolve services to trigger events
$container->get('service.a');
$container->get('service.b');
$container->get('service.c'); // This won't trigger the logging listener

echo "\n4. Custom Filtering\n";
echo "-------------------\n";

// Custom filter for admin services
$container->listen(ServiceResolvedEvent::class, function(ServiceResolvedEvent $event) {
    echo "Admin service resolved: {$event->getId()}\n";
    if ($event->getResolved() instanceof AdminUser) {
        $event->getResolved()->setAdmin(true);
    }
})->where(function(ServiceResolvedEvent $event) {
    return str_starts_with($event->getId(), 'admin.');
});

// This will trigger the admin filter
$container->get('admin.user');

echo "\n5. Before Resolution Events\n";
echo "---------------------------\n";

// Monitor resolution attempts
$container->listen(BeforeResolveEvent::class, function(BeforeResolveEvent $event) {
    echo "About to resolve: {$event->getId()}\n";
});

echo "Resolving services with monitoring:\n";
$container->get('user');

echo "\n=== Examples Complete ===\n";
