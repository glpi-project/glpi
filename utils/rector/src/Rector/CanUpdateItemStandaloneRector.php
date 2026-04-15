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
 * @see \Rector\Tests\TypeDeclaration\Rector\CanUpdateItemStandaloneRector\CanUpdateItemStandaloneRectorTest
 */
final class CanUpdateItemStandaloneRector extends AbstractRector
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
        // Only target canUpdateItem() method calls
        if ($this->getName($node->name) !== 'canUpdateItem') {
            return null;
        }

        // Skip refactoring in test files (tests/units namespace)
        if (str_contains($this->file->getFilePath(), 'tests/units')) {
            return null;
        }

        // Use PHPStan scope to check the context where this method call occurs
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if ($scope instanceof Scope) {
            // Skip if inside canUpdateItem(), canCreateItem() and other internal methods
            // only when the containing class is a CommonDBTM subclass (to avoid infinite recursion on overrides)
            if (in_array($scope->getFunctionName(), ['canUpdateItem', 'canCreateItem', 'canPurgeItem', 'canView'], true)) {
                $class_reflection = $scope->getClassReflection();
                if (
                    $class_reflection instanceof ClassReflection
                    && $this->reflectionProvider->hasClass('CommonDBTM')
                    && $class_reflection->isSubclassOfClass($this->reflectionProvider->getClass('CommonDBTM'))
                ) {
                    return null;
                }
            }

            // Skip if inside the CommonDBTM class itself or any subclass (non-override context)
            $class_reflection = $scope->getClassReflection();
            if ($class_reflection instanceof ClassReflection && $class_reflection->getName() === 'CommonDBTM') {
                return null;
            }
        }

        // Transform: $item->canUpdateItem() → $item->can($item->getID(), UPDATE)
        $var = $node->var;

        return new MethodCall(
            $var,
            new Identifier('can'),
            [
                new Arg(new MethodCall($var, new Identifier('getID'))),
                new Arg(new ConstFetch(new Name('UPDATE'))),
            ]
        );
    }

}
