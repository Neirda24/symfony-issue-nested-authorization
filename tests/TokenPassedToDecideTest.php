<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Attribute\IsGrantedContext;

/**
 * Decides for a token that is not the one in the token storage: the tests that fail show the bug, the others are there for comparison.
 */
final class TokenPassedToDecideTest extends KernelTestCase
{
    #[DataProvider('provideChecksOfRoleAdmin')]
    public function testAnAdminTokenIsGrantedWhileNobodyIsLoggedIn(mixed $attribute): void
    {
        $admin = new UsernamePasswordToken(new InMemoryUser('admin', null, ['ROLE_ADMIN']), 'main', ['ROLE_ADMIN']);

        self::assertTrue(self::getContainer()->get('security.access.decision_manager')->decide($admin, [$attribute]));
    }

    #[DataProvider('provideChecksOfRoleAdmin')]
    public function testAUserTokenIsDeniedWhileAnAdminIsLoggedIn(mixed $attribute): void
    {
        $admin = new UsernamePasswordToken(new InMemoryUser('admin', null, ['ROLE_ADMIN']), 'main', ['ROLE_ADMIN']);
        self::getContainer()->get('security.token_storage')->setToken($admin);
        $user = new UsernamePasswordToken(new InMemoryUser('alice', null, ['ROLE_USER']), 'main', ['ROLE_USER']);

        self::assertFalse(self::getContainer()->get('security.access.decision_manager')->decide($user, [$attribute]));
    }

    public static function provideChecksOfRoleAdmin(): iterable
    {
        yield 'ROLE_ADMIN' => ['ROLE_ADMIN'];
        yield "'ROLE_ADMIN' in role_names, in an expression" => [new Expression("'ROLE_ADMIN' in role_names")];
        yield "is_granted('ROLE_ADMIN'), in an expression" => [new Expression("is_granted('ROLE_ADMIN')")];
        yield "\$context->isGranted('ROLE_ADMIN'), in a closure" => [static fn (IsGrantedContext $context): bool => $context->isGranted('ROLE_ADMIN')];
    }
}
