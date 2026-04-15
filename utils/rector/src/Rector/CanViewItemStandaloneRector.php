<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;

/**
 * @see \Rector\Tests\TypeDeclaration\Rector\CanViewItemStandaloneRector\CanViewItemStandaloneRectorTest
 */
final class CanViewItemStandaloneRector extends AbstractRector
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->getName($node->name) !== 'canViewItem') {
            return null;
        }

        if (str_contains($this->file->getFilePath(), 'tests/units')) {
            return null;
        }

        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if ($scope instanceof Scope) {
            if (in_array($scope->getFunctionName(), ['canViewItem', 'canCreateItem', 'canPurgeItem', 'canView'], true)) {
                $class_reflection = $scope->getClassReflection();
                if (
                    $class_reflection instanceof ClassReflection
                    && $this->reflectionProvider->hasClass('CommonDBTM')
                    && $class_reflection->isSubclassOfClass($this->reflectionProvider->getClass('CommonDBTM'))
                ) {
                    return null;
                }
            }

            $class_reflection = $scope->getClassReflection();
            if ($class_reflection instanceof ClassReflection && $class_reflection->getName() === 'CommonDBTM') {
                return null;
            }
        }

        $var = $node->var;

        return new MethodCall(
            $var,
            new Identifier('can'),
            [
                new Arg(new MethodCall($var, new Identifier('getID'))),
                new Arg(new ConstFetch(new Name('READ'))),
            ]
        );
    }
}

