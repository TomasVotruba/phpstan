<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Cache\Cache;
use PHPStan\File\FileHelper;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use SomeNodeScopeResolverNamespace\Foo;

class NodeScopeResolverTest extends \PHPStan\TestCase
{

	/** @var \PHPStan\Analyser\NodeScopeResolver */
	private $resolver;

	/** @var \PhpParser\PrettyPrinter\Standard */
	private $printer;

	protected function setUp()
	{
		$this->printer = new \PhpParser\PrettyPrinter\Standard();
		$this->resolver = new NodeScopeResolver(
			$this->createBroker(),
			$this->getParser(),
			$this->printer,
			new FileTypeMapper($this->getParser(), $this->createMock(Cache::class)),
			new \PhpParser\BuilderFactory(),
			new FileHelper('/'),
			true,
			true,
			[
				\EarlyTermination\Foo::class => [
					'doFoo',
				],
			]
		);
	}

	public function testClassMethodScope()
	{
		$this->processFile(__DIR__ . '/data/class.php', function (\PhpParser\Node $node, Scope $scope) {
			if ($node instanceof Exit_) {
				$this->assertSame('SomeNodeScopeResolverNamespace', $scope->getNamespace());
				$this->assertSame(Foo::class, $scope->getClassReflection()->getName());
				$this->assertSame('doFoo', $scope->getFunctionName());
				$this->assertSame(Foo::class, $scope->getVariableType('this')->getClass());
				$this->assertTrue($scope->hasVariableType('baz')->yes());
				$this->assertTrue($scope->hasVariableType('lorem')->yes());
				$this->assertFalse($scope->hasVariableType('ipsum')->yes());
				$this->assertTrue($scope->hasVariableType('i')->yes());
				$this->assertTrue($scope->hasVariableType('val')->yes());
				$this->assertSame('SomeNodeScopeResolverNamespace\InvalidArgumentException', $scope->getVariableType('exception')->getClass());
				$this->assertTrue($scope->hasVariableType('staticVariable')->yes());
			}
		});
	}

	public function testAssignInIf()
	{
		$this->processFile(__DIR__ . '/data/if.php', function (\PhpParser\Node $node, Scope $scope) {
			if ($node instanceof Exit_) {
				$this->assertTrue($scope->hasVariableType('foo')->yes());
				$this->assertTrue($scope->hasVariableType('lorem')->yes());
				$this->assertTrue($scope->hasVariableType('callParameter')->yes());
				$this->assertTrue($scope->hasVariableType('arrOne')->yes());
				$this->assertTrue($scope->hasVariableType('arrTwo')->yes());
				$this->assertTrue($scope->hasVariableType('arrTwo')->yes());
				$this->assertTrue($scope->hasVariableType('arrThree')->yes());
				$this->assertSame('string[]', $scope->getVariableType('arrThree')->describe());
				$this->assertTrue($scope->hasVariableType('listedOne')->yes());
				$this->assertTrue($scope->hasVariableType('listedTwo')->yes());
				$this->assertTrue($scope->hasVariableType('listedThree')->yes());
				$this->assertTrue($scope->hasVariableType('listedFour')->yes());
				$this->assertTrue($scope->hasVariableType('inArray')->yes());
				$this->assertTrue($scope->hasVariableType('i')->yes());
				$this->assertTrue($scope->hasVariableType('f')->yes());
				$this->assertTrue($scope->hasVariableType('matches')->yes());
				$this->assertTrue($scope->hasVariableType('anotherArray')->yes());
				$this->assertTrue($scope->hasVariableType('ifVar')->yes());
				$this->assertTrue($scope->hasVariableType('ifNotVar')->no());
				$this->assertTrue($scope->hasVariableType('ifNestedVar')->yes());
				$this->assertTrue($scope->hasVariableType('ifNotNestedVar')->no());
				$this->assertTrue($scope->hasVariableType('matches2')->yes());
				$this->assertTrue($scope->hasVariableType('inTry')->yes());
				$this->assertTrue($scope->hasVariableType('matches3')->yes());
				$this->assertTrue($scope->hasVariableType('matches4')->no());
				$this->assertTrue($scope->hasVariableType('issetBar')->yes());
				$this->assertTrue($scope->hasVariableType('doWhileVar')->yes());
				$this->assertTrue($scope->hasVariableType('switchVar')->yes());
				$this->assertTrue($scope->hasVariableType('noSwitchVar')->no());
				$this->assertTrue($scope->hasVariableType('inTryTwo')->yes());
				$this->assertTrue($scope->hasVariableType('ternaryMatches')->yes());
				$this->assertTrue($scope->hasVariableType('previousI')->yes());
				$this->assertTrue($scope->hasVariableType('previousJ')->yes());
				$this->assertTrue($scope->hasVariableType('frame')->yes());
				$this->assertTrue($scope->hasVariableType('listOne')->yes());
				$this->assertTrue($scope->hasVariableType('listTwo')->yes());
				$this->assertTrue($scope->hasVariableType('e')->yes());
				$this->assertTrue($scope->hasVariableType('exception')->yes());
				$this->assertTrue($scope->hasVariableType('inTryNotInCatch')->no());
				$this->assertTrue($scope->hasVariableType('fooObjectFromTryCatch')->yes());
				$this->assertSame('InTryCatchFoo', $scope->getVariableType('fooObjectFromTryCatch')->describe());
				$this->assertTrue($scope->hasVariableType('mixedVarFromTryCatch')->yes());
				$this->assertSame('float|int', $scope->getVariableType('mixedVarFromTryCatch')->describe());
				$this->assertTrue($scope->hasVariableType('nullableIntegerFromTryCatch')->yes());
				$this->assertSame('int|null', $scope->getVariableType('nullableIntegerFromTryCatch')->describe());
				$this->assertTrue($scope->hasVariableType('anotherNullableIntegerFromTryCatch')->yes());
				$this->assertSame('int|null', $scope->getVariableType('anotherNullableIntegerFromTryCatch')->describe());

				$this->assertSame('(int|null)[]', $scope->getVariableType('nullableIntegers')->describe());
				$this->assertSame('(int|string)[]', $scope->getVariableType('union')->describe());
				$this->assertSame('int|string', $scope->getVariableType('union')->getIterableValueType()->describe());

				$this->assertTrue($scope->hasVariableType('trueOrFalse')->yes());
				$this->assertSame('bool', $scope->getVariableType('trueOrFalse')->describe());
				$this->assertTrue($scope->hasVariableType('falseOrTrue')->yes());
				$this->assertSame('bool', $scope->getVariableType('falseOrTrue')->describe());
				$this->assertTrue($scope->hasVariableType('true')->yes());
				$this->assertSame('true', $scope->getVariableType('true')->describe());
				$this->assertTrue($scope->hasVariableType('false')->yes());
				$this->assertSame('false', $scope->getVariableType('false')->describe());

				$this->assertTrue($scope->hasVariableType('trueOrFalseFromSwitch')->yes());
				$this->assertSame('bool', $scope->getVariableType('trueOrFalseFromSwitch')->describe());
				$this->assertTrue($scope->hasVariableType('trueOrFalseInSwitchWithDefault')->yes());
				$this->assertSame('bool', $scope->getVariableType('trueOrFalseInSwitchWithDefault')->describe());
				$this->assertTrue($scope->hasVariableType('trueOrFalseInSwitchInAllCases')->yes());
				$this->assertSame('bool', $scope->getVariableType('trueOrFalseInSwitchInAllCases')->describe());
				$this->assertTrue($scope->hasVariableType('trueOrFalseInSwitchInAllCasesWithDefault')->yes());
				$this->assertSame('bool', $scope->getVariableType('trueOrFalseInSwitchInAllCasesWithDefault')->describe());
				$this->assertTrue($scope->hasVariableType('trueOrFalseInSwitchInAllCasesWithDefaultCase')->yes());
				$this->assertSame('true', $scope->getVariableType('trueOrFalseInSwitchInAllCasesWithDefaultCase')->describe());
				$this->assertTrue($scope->hasVariableType('variableDefinedInSwitchWithOtherCasesWithEarlyTermination')->yes());
				$this->assertTrue($scope->hasVariableType('anotherVariableDefinedInSwitchWithOtherCasesWithEarlyTermination')->yes());
				$this->assertTrue($scope->hasVariableType('variableDefinedOnlyInEarlyTerminatingSwitchCases')->no());
				$this->assertTrue($scope->hasVariableType('nullableTrueOrFalse')->yes());
				$this->assertSame('bool|null', $scope->getVariableType('nullableTrueOrFalse')->describe());
				$this->assertTrue($scope->hasVariableType('nonexistentVariableOutsideFor')->no());
				$this->assertTrue($scope->hasVariableType('integerOrNullFromFor')->yes());
				$this->assertSame('int|null', $scope->getVariableType('integerOrNullFromFor')->describe());
				$this->assertTrue($scope->hasVariableType('nonexistentVariableOutsideWhile')->no());
				$this->assertTrue($scope->hasVariableType('integerOrNullFromWhile')->yes());
				$this->assertSame('int|null', $scope->getVariableType('integerOrNullFromWhile')->describe());
				$this->assertTrue($scope->hasVariableType('nonexistentVariableOutsideForeach')->no());
				$this->assertTrue($scope->hasVariableType('integerOrNullFromForeach')->yes());
				$this->assertSame('int|null', $scope->getVariableType('integerOrNullFromForeach')->describe());
				$this->assertTrue($scope->hasVariableType('notNullableString')->yes());
				$this->assertSame('string', $scope->getVariableType('notNullableString')->describe());
				$this->assertTrue($scope->hasVariableType('anotherNotNullableString')->yes());
				$this->assertSame('string', $scope->getVariableType('anotherNotNullableString')->describe());
				$this->assertTrue($scope->hasVariableType('nullableString')->yes());
				$this->assertSame('string|null', $scope->getVariableType('nullableString')->describe());
				$this->assertTrue($scope->hasVariableType('alsoNotNullableString')->yes());
				$this->assertSame('string', $scope->getVariableType('alsoNotNullableString')->describe());
				$this->assertTrue($scope->hasVariableType('arrayOfIntegers')->yes());
				$this->assertSame('int[]', $scope->getVariableType('arrayOfIntegers')->describe());
				$this->assertTrue($scope->hasVariableType('arrayAccessObject')->yes());
				$this->assertSame(\ObjectWithArrayAccess\Foo::class, $scope->getVariableType('arrayAccessObject')->describe());
				$this->assertTrue($scope->hasVariableType('width')->yes());
				$this->assertSame('float', $scope->getVariableType('width')->describe());
				$this->assertTrue($scope->hasVariableType('someVariableThatWillGetOverrideInFinally')->yes());
				$this->assertSame('string', $scope->getVariableType('someVariableThatWillGetOverrideInFinally')->describe());
			}
		});
	}

	/**
	 * @requires PHP 7.1
	 */
	public function testArrayDestructuringShortSyntax()
	{
		$this->processFile(__DIR__ . '/data/array-destructuring-short.php', function (\PhpParser\Node $node, Scope $scope) {
			if ($node instanceof Exit_) {
				$this->assertTrue($scope->hasVariableType('a')->yes());
				$this->assertTrue($scope->hasVariableType('b')->yes());
				$this->assertTrue($scope->hasVariableType('c')->yes());
				$this->assertTrue($scope->hasVariableType('d')->yes());
				$this->assertTrue($scope->hasVariableType('e')->yes());
			}
		});
	}

	public function dataParameterTypes(): array
	{
		return [
			[
				'int',
				'$integer',
			],
			[
				'bool',
				'$boolean',
			],
			[
				'string',
				'$string',
			],
			[
				'float',
				'$float',
			],
			[
				'TypesNamespaceTypehints\Lorem',
				'$loremObject',
			],
			[
				'mixed',
				'$mixed',
			],
			[
				'mixed[]',
				'$array',
			],
			[
				'bool|null',
				'$isNullable',
			],
			[
				'TypesNamespaceTypehints\Lorem',
				'$loremObjectRef',
			],
			[
				'TypesNamespaceTypehints\Bar',
				'$barObject',
			],
			[
				'TypesNamespaceTypehints\Foo',
				'$fooObject',
			],
			[
				'TypesNamespaceTypehints\Bar',
				'$anotherBarObject',
			],
			[
				'callable',
				'$callable',
			],
			[
				'string[]',
				'$variadicStrings',
			],
			[
				'string',
				'$variadicStrings[0]',
			],
		];
	}

	/**
	 * @dataProvider dataParameterTypes
	 * @param string $typeClass
	 * @param string $expression
	 */
	public function testTypehints(
		string $typeClass,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/typehints.php',
			$typeClass,
			$expression
		);
	}

	public function dataAnonymousFunctionParameterTypes(): array
	{
		return [
			[
				'int',
				'$integer',
			],
			[
				'bool',
				'$boolean',
			],
			[
				'string',
				'$string',
			],
			[
				'float',
				'$float',
			],
			[
				'TypesNamespaceTypehints\Lorem',
				'$loremObject',
			],
			[
				'mixed',
				'$mixed',
			],
			[
				'mixed[]',
				'$array',
			],
			[
				'bool|null',
				'$isNullable',
			],
			[
				'callable',
				'$callable',
			],
			[
				'TypesNamespaceTypehints\FooWithAnonymousFunction',
				'$self',
			],
		];
	}

	/**
	 * @dataProvider dataAnonymousFunctionParameterTypes
	 * @param string $description
	 * @param string $expression
	 */
	public function testAnonymousFunctionTypehints(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/typehints-anonymous-function.php',
			$description,
			$expression
		);
	}

	public function dataVarAnnotations(): array
	{
		return [
			[
				'int',
				'$integer',
			],
			[
				'bool',
				'$boolean',
			],
			[
				'string',
				'$string',
			],
			[
				'float',
				'$float',
			],
			[
				'VarAnnotations\Lorem',
				'$loremObject',
			],
			[
				'AnotherNamespace\Bar',
				'$barObject',
			],
			[
				'mixed',
				'$mixed',
			],
			[
				'mixed[]',
				'$array',
			],
			[
				'bool|null',
				'$isNullable',
			],
			[
				'callable',
				'$callable',
			],
			[
				'VarAnnotations\Foo',
				'$self',
			],
			[
				'float',
				'$invalidInteger',
			],
			[
				'static(VarAnnotations\Foo)',
				'$static',
			],
		];
	}

	/**
	 * @dataProvider dataVarAnnotations
	 * @param string $description
	 * @param string $expression
	 */
	public function testVarAnnotations(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/var-annotations.php',
			$description,
			$expression
		);
	}

	/**
	 * @dataProvider dataVarAnnotations
	 * @param string $description
	 * @param string $expression
	 */
	public function testVarAnnotationsAlt(
		string $description,
		string $expression
	)
	{
		$description = str_replace('VarAnnotations\\', 'VarAnnotationsAlt\\', $description);
		$this->assertTypes(
			__DIR__ . '/data/var-annotations-alt.php',
			$description,
			$expression
		);
	}

	public function dataCasts(): array
	{
		return [
			[
				'int',
				'$castedInteger',
			],
			[
				'bool',
				'$castedBoolean',
			],
			[
				'float',
				'$castedFloat',
			],
			[
				'string',
				'$castedString',
			],
			[
				'mixed[]',
				'$castedArray',
			],
			[
				'stdClass',
				'$castedObject',
			],
			[
				'null',
				'$castedNull',
			],
		];
	}

	/**
	 * @dataProvider dataCasts
	 * @param string $desciptiion
	 * @param string $expression
	 */
	public function testCasts(
		string $desciptiion,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/casts.php',
			$desciptiion,
			$expression
		);
	}

	public function dataDeductedTypes(): array
	{
		return [
			[
				'int',
				'$integerLiteral',
			],
			[
				'true',
				'$booleanLiteral',
			],
			[
				'false',
				'$anotherBooleanLiteral',
			],
			[
				'string',
				'$stringLiteral',
			],
			[
				'float',
				'$floatLiteral',
			],
			[
				'float',
				'$floatAssignedByRef',
			],
			[
				'null',
				'$nullLiteral',
			],
			[
				'TypesNamespaceDeductedTypes\Lorem',
				'$loremObjectLiteral',
			],
			[
				'mixed',
				'$mixedObjectLiteral',
			],
			[
				'static(TypesNamespaceDeductedTypes\Foo)',
				'$newStatic',
			],
			[
				'mixed[]',
				'$arrayLiteral',
			],
			[
				'string',
				'$stringFromFunction',
			],
			[
				'TypesNamespaceFunctions\Foo',
				'$fooObjectFromFunction',
			],
			[
				'mixed',
				'$mixedFromFunction',
			],
			[
				'int',
				'\TypesNamespaceDeductedTypes\Foo::INTEGER_CONSTANT',
			],
			[
				'int',
				'self::INTEGER_CONSTANT',
			],
			[
				'float',
				'self::FLOAT_CONSTANT',
			],
			[
				'string',
				'self::STRING_CONSTANT',
			],
			[
				'mixed[]',
				'self::ARRAY_CONSTANT',
			],
			[
				'bool',
				'self::BOOLEAN_CONSTANT',
			],
			[
				'null',
				'self::NULL_CONSTANT',
			],
			[
				'int',
				'$foo::INTEGER_CONSTANT',
			],
			[
				'float',
				'$foo::FLOAT_CONSTANT',
			],
			[
				'string',
				'$foo::STRING_CONSTANT',
			],
			[
				'mixed[]',
				'$foo::ARRAY_CONSTANT',
			],
			[
				'bool',
				'$foo::BOOLEAN_CONSTANT',
			],
			[
				'null',
				'$foo::NULL_CONSTANT',
			],
		];
	}

	/**
	 * @dataProvider dataDeductedTypes
	 * @param string $description
	 * @param string $expression
	 */
	public function testDeductedTypes(
		string $description,
		string $expression
	)
	{
		require_once __DIR__ . '/data/function-definitions.php';
		$this->assertTypes(
			__DIR__ . '/data/deducted-types.php',
			$description,
			$expression
		);
	}

	public function dataProperties(): array
	{
		return [
			[
				'mixed',
				'$this->mixedProperty',
			],
			[
				'mixed',
				'$this->alsoMixedProperty',
			],
			[
				'mixed',
				'$this->anotherMixedProperty',
			],
			[
				'mixed',
				'$this->yetAnotherMixedProperty',
			],
			[
				'int',
				'$this->integerProperty',
			],
			[
				'int',
				'$this->anotherIntegerProperty',
			],
			[
				'mixed[]',
				'$this->arrayPropertyOne',
			],
			[
				'mixed[]',
				'$this->arrayPropertyOther',
			],
			[
				'PropertiesNamespace\\Lorem',
				'$this->objectRelative',
			],
			[
				'SomeOtherNamespace\\Ipsum',
				'$this->objectFullyQualified',
			],
			[
				'SomeNamespace\\Amet',
				'$this->objectUsed',
			],
			[
				'mixed',
				'$this->nonexistentProperty',
			],
			[
				'int|null',
				'$this->nullableInteger',
			],
			[
				'SomeNamespace\Amet|null',
				'$this->nullableObject',
			],
			[
				'PropertiesNamespace\\Foo',
				'$this->selfType',
			],
			[
				'static(PropertiesNamespace\Foo)',
				'$this->staticType',
			],
			[
				'null',
				'$this->nullType',
			],
			[
				'SomeNamespace\Sit',
				'$this->inheritedProperty',
			],
			[
				'PropertiesNamespace\Bar',
				'$this->barObject->doBar()',
			],
			[
				'mixed',
				'$this->invalidTypeProperty',
			],
			[
				'resource',
				'$this->resource',
			],
			[
				'mixed',
				'$yetAnotherAnotherMixedParameter',
			],
			[
				'mixed',
				'$yetAnotherAnotherAnotherMixedParameter',
			],
			[
				'mixed',
				'$yetAnotherAnotherAnotherAnotherMixedParameter',
			],
			[
				'string',
				'self::$staticStringProperty',
			],
			[
				'SomeGroupNamespace\One',
				'$this->groupUseProperty',
			],
			[
				'SomeGroupNamespace\Two',
				'$this->anotherGroupUseProperty',
			],
			[
				'PropertiesNamespace\Bar',
				'$this->inheritDocProperty',
			],
		];
	}

	/**
	 * @dataProvider dataProperties
	 * @param string $description
	 * @param string $expression
	 */
	public function testProperties(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/properties.php',
			$description,
			$expression
		);
	}

	public function dataBinaryOperations(): array
	{
		$typeCallback = function ($value) {
			$type = gettype($value);
			if ($type === 'integer') {
				return 'int';
			} elseif ($type === 'double') {
				return 'float';
			} elseif ($type === 'boolean') {
				return 'bool';
			} elseif ($type === 'string') {
				return $type;
			}

			return MixedType::class;
		};

		return [
			[
				$typeCallback('foo' && 'bar'),
				"'foo' && 'bar'",
			],
			[
				$typeCallback('foo' || 'bar'),
				"'foo' || 'bar'",
			],
			[
				$typeCallback('foo' xor 'bar'),
				"'foo' xor 'bar'",
			],
			[
				$typeCallback(!2),
				'!2',
			],
			[
				$typeCallback(-1),
				'-1',
			],
			[
				$typeCallback(+1),
				'+1',
			],
			// integer + integer
			[
				$typeCallback(1 + 1),
				'1 + 1',
			],
			[
				$typeCallback(1 - 1),
				'1 - 1',
			],
			[
				$typeCallback(1 / 2),
				'1 / 2',
			],
			[
				$typeCallback(1 * 1),
				'1 * 1',
			],
			[
				$typeCallback(1 ** 1),
				'1 ** 1',
			],
			[
				$typeCallback(1 % 1),
				'1 % 1',
			],
			[
				$typeCallback(1 / 2),
				'$integer /= 2',
			],
			[
				$typeCallback(1 * 1),
				'$integer *= 1',
			],
			// float + float
			[
				$typeCallback(1.2 + 1.4),
				'1.2 + 1.4',
			],
			[
				$typeCallback(1.2 - 1.4),
				'1.2 - 1.4',
			],
			[
				$typeCallback(1.2 / 2.4),
				'1.2 / 2.4',
			],
			[
				$typeCallback(1.2 * 1.4),
				'1.2 * 1.4',
			],
			[
				$typeCallback(1.2 ** 1.4),
				'1.2 ** 1.4',
			],
			[
				$typeCallback(3.2 % 2.4),
				'3.2 % 2.4',
			],
			[
				$typeCallback(1.2 / 2.4),
				'$float /= 2.4',
			],
			[
				$typeCallback(1.2 * 2.4),
				'$float *= 2.4',
			],
			// integer + float
			[
				$typeCallback(1 + 1.4),
				'1 + 1.4',
			],
			[
				$typeCallback(1 - 1.4),
				'1 - 1.4',
			],
			[
				$typeCallback(1 / 2.4),
				'1 / 2.4',
			],
			[
				$typeCallback(1 * 1.4),
				'1 * 1.4',
			],
			[
				$typeCallback(1 ** 1.4),
				'1 ** 1.4',
			],
			[
				$typeCallback(3 % 2.4),
				'3 % 2.4',
			],
			[
				$typeCallback(1 / 2.4),
				'$integer /= 2.4',
			],
			[
				$typeCallback(1 * 2.4),
				'$integer *= 2.4',
			],
			// float + integer
			[
				$typeCallback(1.2 + 1),
				'1.2 + 1',
			],
			[
				$typeCallback(1.2 - 1),
				'1.2 - 1',
			],
			[
				$typeCallback(1.2 / 2),
				'1.2 / 2',
			],
			[
				$typeCallback(1.2 * 1),
				'1.2 * 1',
			],
			[
				$typeCallback(1.2 ** 1),
				'1.2 ** 1',
			],
			[
				$typeCallback(3.2 % 2),
				'3.2 % 2',
			],
			[
				$typeCallback(1.2 / 2.4),
				'$float /= 2.4',
			],
			[
				$typeCallback(1.2 * 2),
				'$float *= 2',
			],
			// boolean
			[
				$typeCallback(true + false),
				'true + false',
			],
			// string
			[
				$typeCallback('a' . 'b'),
				"'a' . 'b'",
			],
			[
				$typeCallback(1 . 'b'),
				"1 . 'b'",
			],
			[
				$typeCallback(1.0 . 'b'),
				"1.0 . 'b'",
			],
			[
				$typeCallback(1.0 . 2.0),
				'1.0 . 2.0',
			],
			[
				$typeCallback('foo' <=> 'bar'),
				"'foo' <=> 'bar'",
			],
			[
				'mixed',
				'1 + doFoo()',
			],
			[
				'mixed',
				'1 / doFoo()',
			],
			[
				'mixed',
				'1.0 / doFoo()',
			],
			[
				'mixed',
				'doFoo() / 1',
			],
			[
				'mixed',
				'doFoo() / 1.0',
			],
			[
				'mixed',
				'1.0 + doFoo()',
			],
			[
				'mixed',
				'1.0 + doFoo()',
			],
			[
				'mixed',
				'doFoo() + 1',
			],
			[
				'mixed',
				'doFoo() + 1.0',
			],
			[
				'string|null',
				"doFoo() ? 'foo' : null",
			],
			[
				'int|null',
				'12 ?: null',
			],
			[
				'int|string',
				'$string ?: 12',
			],
			[
				'int|string',
				'$stringOrNull ?: 12',
			],
			[
				'int',
				'$integer ?: 12',
			],
			[
				'string|null',
				"'foo' ?? null",
			],
			[
				'string',
				'$string ?? \'foo\'',
			],
			[
				'string',
				'$stringOrNull ?? \'foo\'',
			],
			[
				'int|string',
				'$string ?? $integer',
			],
			[
				'int|string',
				'$stringOrNull ?? $integer',
			],
			[
				'string',
				'\Foo::class',
			],
			[
				'int',
				'__LINE__',
			],
			[
				'string',
				'__DIR__',
			],
			[
				'int', // if the only argument in min is array, lowest value in that array is returned
				'min([1, 2, 3])',
			],
			[
				'int[]',
				'min([1, 2, 3], [4, 5, 5])',
			],
			[
				'int',
				'min(...[1, 2, 3])',
			],
			[
				'float',
				'min(...[1.1, 2.2, 3.3])',
			],
			[
				'float|int',
				'min(...[1.1, 2, 3])',
			],
			[
				'int',
				'max(...[1, 2, 3])',
			],
			[
				'float',
				'max(...[1.1, 2.2, 3.3])',
			],
			[
				'int',
				'min(1, 2, 3)',
			],
			[
				'int',
				'max(1, 2, 3)',
			],
			[
				'float',
				'min(1.1, 2.2, 3.3)',
			],
			[
				'float',
				'max(1.1, 2.2, 3.3)',
			],
			[
				'DateTimeImmutable',
				'max(new \DateTimeImmutable("today"), new \DateTimeImmutable("tomorrow"))',
			],
			[
				'float|int',
				'min(1, 2.2, 3.3)',
			],
			[
				'string',
				'"Hello $world"',
			],
			[
				'string',
				'$string .= "str"',
			],
			[
				'int',
				'$integer <<= 2.2',
			],
			[
				'int',
				'$float >>= 2.2',
			],
		];
	}

	/**
	 * @dataProvider dataBinaryOperations
	 * @param string $description
	 * @param string $expression
	 */
	public function testBinaryOperations(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/binary.php',
			$description,
			$expression
		);
	}

	public function dataCloneOperators(): array
	{
		return [
			[
				'CloneOperators\Foo',
				'clone $fooObject',
			],
		];
	}

	/**
	 * @dataProvider dataCloneOperators
	 * @param string $description
	 * @param string $expression
	 */
	public function testCloneOperators(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/clone.php',
			$description,
			$expression
		);
	}

	public function dataLiteralArrays(): array
	{
		return [
			[
				'int',
				'$integers[0]',
			],
			[
				'string',
				'$strings[0]',
			],
			[
				'mixed',
				'$emptyArray[0]',
			],
			[
				'int|string',
				'$mixedArray[0]',
			],
			[
				'bool',
				'$integers[0] >= $integers[1] - 1',
			],
		];
	}

	/**
	 * @dataProvider dataLiteralArrays
	 * @param string $description
	 * @param string $expression
	 */
	public function testLiteralArrays(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/literal-arrays.php',
			$description,
			$expression
		);
	}

	public function dataTypeFromFunctionPhpDocs(): array
	{
		return [
			[
				'mixed',
				'$mixedParameter',
			],
			[
				'mixed',
				'$alsoMixedParameter',
			],
			[
				'mixed',
				'$anotherMixedParameter',
			],
			[
				'mixed',
				'$yetAnotherMixedParameter',
			],
			[
				'int',
				'$integerParameter',
			],
			[
				'int',
				'$anotherIntegerParameter',
			],
			[
				'mixed[]',
				'$arrayParameterOne',
			],
			[
				'mixed[]',
				'$arrayParameterOther',
			],
			[
				'MethodPhpDocsNamespace\\Lorem',
				'$objectRelative',
			],
			[
				'SomeOtherNamespace\\Ipsum',
				'$objectFullyQualified',
			],
			[
				'SomeNamespace\\Amet',
				'$objectUsed',
			],
			[
				'mixed',
				'$nonexistentParameter',
			],
			[
				'int|null',
				'$nullableInteger',
			],
			[
				'SomeNamespace\Amet|null',
				'$nullableObject',
			],
			[
				'SomeNamespace\Amet|null',
				'$anotherNullableObject',
			],
			[
				'null',
				'$nullType',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'$barObject->doBar()',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'$conflictedObject',
			],
			[
				'MethodPhpDocsNamespace\Baz',
				'$moreSpecifiedObject',
			],
			[
				'MethodPhpDocsNamespace\Baz',
				'$moreSpecifiedObject->doFluent()',
			],
			[
				'MethodPhpDocsNamespace\Baz|null',
				'$moreSpecifiedObject->doFluentNullable()',
			],
			[
				'MethodPhpDocsNamespace\Baz',
				'$moreSpecifiedObject->doFluentArray()[0]',
			],
			[
				'MethodPhpDocsNamespace\Baz[]|MethodPhpDocsNamespace\Baz|MethodPhpDocsNamespace\Foo',
				'$moreSpecifiedObject->doFluentUnionIterable()',
			],
			[
				'MethodPhpDocsNamespace\Baz',
				'$fluentUnionIterableBaz',
			],
			[
				'resource',
				'$resource',
			],
			[
				'mixed',
				'$yetAnotherAnotherMixedParameter',
			],
			[
				'mixed',
				'$yetAnotherAnotherAnotherMixedParameter',
			],
			[
				'void',
				'$voidParameter',
			],
			[
				'SomeNamespace\Consecteur',
				'$useWithoutAlias',
			],
			[
				'true',
				'$true',
			],
			[
				'false',
				'$false',
			],
			[
				'true',
				'$boolTrue',
			],
			[
				'false',
				'$boolFalse',
			],
			[
				'bool',
				'$trueBoolean',
			],
			[
				'bool',
				'$parameterWithDefaultValueFalse',
			],
		];
	}

	public function dataTypeFromFunctionFunctionPhpDocs(): array
	{
		return [
			[
				'MethodPhpDocsNamespace\Foo',
				'$fooFunctionResult',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'$barFunctionResult',
			],
		];
	}

	/**
	 * @dataProvider dataTypeFromFunctionPhpDocs
	 * @dataProvider dataTypeFromFunctionFunctionPhpDocs
	 * @param string $description
	 * @param string $expression
	 */
	public function testTypeFromFunctionPhpDocs(
		string $description,
		string $expression
	)
	{
		require_once __DIR__ . '/data/functionPhpDocs.php';
		$this->assertTypes(
			__DIR__ . '/data/functionPhpDocs.php',
			$description,
			$expression
		);
	}

	public function dataTypeFromMethodPhpDocs(): array
	{
		return [
			[
				'MethodPhpDocsNamespace\\Foo',
				'$selfType',
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'$staticType',
				false,
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$this->doFoo()',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'static::doSomethingStatic()',
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'parent::doLorem()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$parent->doLorem()',
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'$this->doLorem()',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$differentInstance->doLorem()',
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'parent::doIpsum()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$parent->doIpsum()',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$differentInstance->doIpsum()',
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'$this->doIpsum()',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$this->doBar()[0]',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'self::doSomethingStatic()',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'\MethodPhpDocsNamespace\Foo::doSomethingStatic()',
			],
			[
				'$this(MethodPhpDocsNamespace\Foo)',
				'parent::doThis()',
			],
			[
				'$this(MethodPhpDocsNamespace\Foo)|null',
				'parent::doThisNullable()',
			],
			[
				'$this(MethodPhpDocsNamespace\Foo)|MethodPhpDocsNamespace\Bar|null',
				'parent::doThisUnion()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$this->returnParent()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$this->returnPhpDocParent()',
			],
			[
				'null[]',
				'$this->returnNulls()',
			],
		];
	}

	/**
	 * @dataProvider dataTypeFromFunctionPhpDocs
	 * @dataProvider dataTypeFromMethodPhpDocs
	 * @param string $description
	 * @param string $expression
	 */
	public function testTypeFromMethodPhpDocs(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/methodPhpDocs.php',
			$description,
			$expression
		);
	}

	/**
	 * @dataProvider dataTypeFromFunctionPhpDocs
	 * @dataProvider dataTypeFromMethodPhpDocs
	 * @param string $description
	 * @param string $expression
	 * @param bool $replaceClass
	 */
	public function testTypeFromMethodPhpDocsInheritDoc(
		string $description,
		string $expression,
		bool $replaceClass = true
	)
	{
		if ($replaceClass) {
			$description = str_replace('$this(MethodPhpDocsNamespace\Foo)', '$this(MethodPhpDocsNamespace\FooInheritDocChild)', $description);
			$description = str_replace('static(MethodPhpDocsNamespace\Foo)', 'static(MethodPhpDocsNamespace\FooInheritDocChild)', $description);
		}
		$this->assertTypes(
			__DIR__ . '/data/method-phpDocs-inheritdoc.php',
			$description,
			$expression
		);
	}

	public function dataInstanceOf(): array
	{
		return [
			[
				'PhpParser\Node\Expr\ArrayDimFetch',
				'$foo',
			],
			[
				'PhpParser\Node\Stmt\Function_',
				'$bar',
			],
			[
				'mixed',
				'$baz',
			],
			[
				'InstanceOfNamespace\Lorem',
				'$lorem',
			],
			[
				'InstanceOfNamespace\Dolor',
				'$dolor',
			],
			[
				'InstanceOfNamespace\Sit',
				'$sit',
			],
			[
				'InstanceOfNamespace\Foo',
				'$self',
			],
			[
				'static(InstanceOfNamespace\Foo)',
				'$static',
			],
			[
				'static(InstanceOfNamespace\Foo)',
				'clone $static',
			],
		];
	}

	/**
	 * @dataProvider dataInstanceOf
	 * @param string $description
	 * @param string $expression
	 */
	public function testInstanceOf(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/instanceof.php',
			$description,
			$expression
		);
	}

	public function testNotSwitchInstanceof()
	{
		$this->assertTypes(
			__DIR__ . '/data/switch-instanceof-not.php',
			'mixed',
			'$foo'
		);
	}

	public function dataSwitchInstanceOf(): array
	{
		return [
			[
				'mixed',
				'$foo',
			],
			[
				'SwitchInstanceOf\Bar',
				'$bar',
			],
			[
				'SwitchInstanceOf\Baz',
				'$baz',
			],
		];
	}

	/**
	 * @dataProvider dataSwitchInstanceOf
	 * @param string $description
	 * @param string $expression
	 */
	public function testSwitchInstanceof(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/switch-instanceof.php',
			$description,
			$expression
		);
	}

	public function dataSwitchGetClass(): array
	{
		return [
			[
				'SwitchGetClass\Lorem',
				'$lorem',
			],
		];
	}

	/**
	 * @dataProvider dataSwitchGetClass
	 * @param string $description
	 * @param string $expression
	 */
	public function testSwitchGetClass(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/switch-get-class.php',
			$description,
			$expression
		);
	}

	public function dataDynamicMethodReturnTypeExtensions(): array
	{
		return [
			[
				'mixed',
				'$em->getByFoo($foo)',
			],
			[
				'DynamicMethodReturnTypesNamespace\Entity',
				'$em->getByPrimary()',
			],
			[
				'DynamicMethodReturnTypesNamespace\Entity',
				'$em->getByPrimary($foo)',
			],
			[
				'DynamicMethodReturnTypesNamespace\Foo',
				'$em->getByPrimary(DynamicMethodReturnTypesNamespace\Foo::class)',
			],
			[
				'mixed',
				'$iem->getByFoo($foo)',
			],
			[
				'DynamicMethodReturnTypesNamespace\Entity',
				'$iem->getByPrimary()',
			],
			[
				'DynamicMethodReturnTypesNamespace\Entity',
				'$iem->getByPrimary($foo)',
			],
			[
				'DynamicMethodReturnTypesNamespace\Foo',
				'$iem->getByPrimary(DynamicMethodReturnTypesNamespace\Foo::class)',
			],
			[
				'mixed',
				'EntityManager::getByFoo($foo)',
			],
			[
				'DynamicMethodReturnTypesNamespace\EntityManager',
				'\DynamicMethodReturnTypesNamespace\EntityManager::createManagerForEntity()',
			],
			[
				'DynamicMethodReturnTypesNamespace\EntityManager',
				'\DynamicMethodReturnTypesNamespace\EntityManager::createManagerForEntity($foo)',
			],
			[
				'DynamicMethodReturnTypesNamespace\Foo',
				'\DynamicMethodReturnTypesNamespace\EntityManager::createManagerForEntity(DynamicMethodReturnTypesNamespace\Foo::class)',
			],
			[
				'mixed',
				'\DynamicMethodReturnTypesNamespace\InheritedEntityManager::getByFoo($foo)',
			],
			[
				'DynamicMethodReturnTypesNamespace\EntityManager',
				'\DynamicMethodReturnTypesNamespace\InheritedEntityManager::createManagerForEntity()',
			],
			[
				'DynamicMethodReturnTypesNamespace\EntityManager',
				'\DynamicMethodReturnTypesNamespace\InheritedEntityManager::createManagerForEntity($foo)',
			],
			[
				'DynamicMethodReturnTypesNamespace\Foo',
				'\DynamicMethodReturnTypesNamespace\InheritedEntityManager::createManagerForEntity(DynamicMethodReturnTypesNamespace\Foo::class)',
			],
		];
	}

	/**
	 * @dataProvider dataDynamicMethodReturnTypeExtensions
	 * @param string $description
	 * @param string $expression
	 */
	public function testDynamicMethodReturnTypeExtensions(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/dynamic-method-return-types.php',
			$description,
			$expression,
			[
				new class() implements DynamicMethodReturnTypeExtension {

					public static function getClass(): string
					{
						return \DynamicMethodReturnTypesNamespace\EntityManager::class;
					}

					public function isMethodSupported(MethodReflection $methodReflection): bool
					{
						return in_array($methodReflection->getName(), ['getByPrimary'], true);
					}

					public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): \PHPStan\Type\Type
					{
						$args = $methodCall->args;
						if (count($args) === 0) {
							return $methodReflection->getReturnType();
						}

						$arg = $args[0]->value;
						if (!($arg instanceof \PhpParser\Node\Expr\ClassConstFetch)) {
							return $methodReflection->getReturnType();
						}

						if (!($arg->class instanceof \PhpParser\Node\Name)) {
							return $methodReflection->getReturnType();
						}

						return new ObjectType((string) $arg->class);
					}
				},
			],
			[
				new class() implements DynamicStaticMethodReturnTypeExtension {

					public static function getClass(): string
					{
						return \DynamicMethodReturnTypesNamespace\EntityManager::class;
					}

					public function isStaticMethodSupported(MethodReflection $methodReflection): bool
					{
						return in_array($methodReflection->getName(), ['createManagerForEntity'], true);
					}

					public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): \PHPStan\Type\Type
					{
						$args = $methodCall->args;
						if (count($args) === 0) {
							return $methodReflection->getReturnType();
						}

						$arg = $args[0]->value;
						if (!($arg instanceof \PhpParser\Node\Expr\ClassConstFetch)) {
							return $methodReflection->getReturnType();
						}

						if (!($arg->class instanceof \PhpParser\Node\Name)) {
							return $methodReflection->getReturnType();
						}

						return new ObjectType((string) $arg->class);
					}
				},
			]
		);
	}

	public function dataOverwritingVariable(): array
	{
		return [
			[
				'mixed',
				'$var',
				'new \OverwritingVariable\Bar();',
			],
			[
				'OverwritingVariable\Bar',
				'$var',
				'$var->methodFoo();',
			],
			[
				'OverwritingVariable\Foo',
				'$var',
				'die;',
			],
		];
	}

	/**
	 * @dataProvider dataOverwritingVariable
	 * @param string $description
	 * @param string $expression
	 * @param string $evaluatedPointExpressionType
	 */
	public function testOverwritingVariable(
		string $description,
		string $expression,
		string $evaluatedPointExpressionType
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/overwritingVariable.php',
			$description,
			$expression,
			[],
			[],
			$evaluatedPointExpressionType
		);
	}

	public function dataNegatedInstanceof(): array
	{
		return [
			[
				'NegatedInstanceOf\Foo',
				'$foo',
			],
			[
				'NegatedInstanceOf\Bar',
				'$bar',
			],
			[
				'mixed',
				'$lorem',
			],
			[
				'mixed',
				'$dolor',
			],
			[
				'mixed',
				'$sit',
			],
			[
				'mixed',
				'$mixedFoo',
			],
			[
				'mixed',
				'$mixedBar',
			],
			[
				'NegatedInstanceOf\Foo',
				'$self',
			],
			[
				'static(NegatedInstanceOf\Foo)',
				'$static',
			],
		];
	}

	/**
	 * @dataProvider dataNegatedInstanceof
	 * @param string $description
	 * @param string $expression
	 */
	public function testNegatedInstanceof(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/negated-instanceof.php',
			$description,
			$expression
		);
	}

	public function dataAnonymousFunction(): array
	{
		return [
			[
				'string',
				'$str',
			],
			[
				'int',
				'$integer',
			],
			[
				'mixed',
				'$bar',
			],
		];
	}

	/**
	 * @dataProvider dataAnonymousFunction
	 * @param string $description
	 * @param string $expression
	 */
	public function testAnonymousFunction(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/anonymous-function.php',
			$description,
			$expression
		);
	}

	public function dataForeachArrayType(): array
	{
		return [
			[
				__DIR__ . '/data/foreach/array-object-type.php',
				'AnotherNamespace\Foo',
				'$foo',
			],
			[
				__DIR__ . '/data/foreach/array-object-type.php',
				'AnotherNamespace\Foo',
				'$foos[0]',
			],
			[
				__DIR__ . '/data/foreach/array-object-type.php',
				'int',
				'self::ARRAY_CONSTANT[0]',
			],
			[
				__DIR__ . '/data/foreach/array-object-type.php',
				'int|string',
				'self::MIXED_CONSTANT[0]',
			],
			[
				__DIR__ . '/data/foreach/nested-object-type.php',
				'AnotherNamespace\Foo',
				'$foo',
			],
			[
				__DIR__ . '/data/foreach/nested-object-type.php',
				'AnotherNamespace\Foo',
				'$foos[0]',
			],
			[
				__DIR__ . '/data/foreach/nested-object-type.php',
				'AnotherNamespace\Foo',
				'$fooses[0][0]',
			],
			[
				__DIR__ . '/data/foreach/integer-type.php',
				'int',
				'$integer',
			],
			[
				__DIR__ . '/data/foreach/reusing-specified-variable.php',
				'int',
				'$business',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-variable-first.php',
				'callable',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-variable-second.php',
				'stdClass',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-no-variable.php',
				'mixed',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-wrong-variable.php',
				'mixed',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-variable-with-reference.php',
				'string',
				'$value',
			],
		];
	}

	/**
	 * @dataProvider dataForeachArrayType
	 * @param string $file
	 * @param string $description
	 * @param string $expression
	 */
	public function testForeachArrayType(
		string $file,
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			$file,
			$description,
			$expression
		);
	}

	public function dataOverridingSpecifiedType(): array
	{
		return [
			[
				__DIR__ . '/data/catch-specified-variable.php',
				'TryCatchWithSpecifiedVariable\FooException',
				'$foo',
			],
		];
	}

	/**
	 * @dataProvider dataOverridingSpecifiedType
	 * @param string $file
	 * @param string $description
	 * @param string $expression
	 */
	public function testOverridingSpecifiedType(
		string $file,
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			$file,
			$description,
			$expression
		);
	}

	public function dataForeachObjectType(): array
	{
		return [
			[
				__DIR__ . '/data/foreach/object-type.php',
				'ObjectType\\MyKey',
				'$keyFromIterator',
				"'insideFirstForeach';",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'ObjectType\\MyValue',
				'$valueFromIterator',
				"'insideFirstForeach';",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'ObjectType\\MyKey',
				'$keyFromAggregate',
				"'insideSecondForeach';",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'ObjectType\\MyValue',
				'$valueFromAggregate',
				"'insideSecondForeach';",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'mixed', // *ERROR*
				'$keyFromRecursiveAggregate',
				"'insideThirdForeach';",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'mixed', // *ERROR*
				'$valueFromRecursiveAggregate',
				"'insideThirdForeach';",
			],
		];
	}

	/**
	 * @dataProvider dataForeachObjectType
	 * @param string $file
	 * @param string $description
	 * @param string $expression
	 * @param string $evaluatedPointExpression
	 */
	public function testForeachObjectType(
		string $file,
		string $description,
		string $expression,
		string $evaluatedPointExpression
	)
	{
		$this->assertTypes(
			$file,
			$description,
			$expression,
			[],
			[],
			$evaluatedPointExpression
		);
	}

	public function dataArrayFunctions(): array
	{
		return [
			[
				'int',
				'$integers[0]',
			],
			[
				'string',
				'$mappedStrings[0]',
			],
			[
				'int',
				'$filteredIntegers[0]',
			],
			[
				'int',
				'$uniquedIntegers[0]',
			],
			[
				'string',
				'$reducedIntegersToString',
			],
			[
				'int',
				'$reversedIntegers[0]',
			],
			[
				'int[]',
				'$filledIntegers',
			],
			[
				'int[]',
				'$filledIntegersWithKeys',
			],
		];
	}

	/**
	 * @dataProvider dataArrayFunctions
	 * @param string $description
	 * @param string $expression
	 */
	public function testArrayFunctions(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/array-functions.php',
			$description,
			$expression
		);
	}

	public function dataSpecifiedTypesUsingIsFunctions(): array
	{
		return [
			[
				'int',
				'$integer',
			],
			[
				'int',
				'$anotherInteger',
			],
			[
				'int',
				'$longInteger',
			],
			[
				'float',
				'$float',
			],
			[
				'float',
				'$doubleFloat',
			],
			[
				'float',
				'$realFloat',
			],
			[
				'null',
				'$null',
			],
			[
				'mixed[]',
				'$array',
			],
			[
				'bool',
				'$bool',
			],
			[
				'callable',
				'$callable',
			],
			[
				'resource',
				'$resource',
			],
			[
				'int',
				'$yetAnotherInteger',
			],
			[
				'mixed',
				'$mixedInteger',
			],
			[
				'string',
				'$string',
			],
		];
	}

	/**
	 * @dataProvider dataSpecifiedTypesUsingIsFunctions
	 * @param string $description
	 * @param string $expression
	 */
	public function testSpecifiedTypesUsingIsFunctions(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/specifiedTypesUsingIsFunctions.php',
			$description,
			$expression
		);
	}

	public function dataIterable(): array
	{
		return [
			[
				'iterable(mixed[])',
				'$this->iterableProperty',
			],
			[
				'iterable(mixed[])',
				'$iterableSpecifiedLater',
			],
			[
				'iterable(mixed[])',
				'$iterableWithoutTypehint',
			],
			[
				'mixed',
				'$iterableWithoutTypehint[0]',
			],
			[
				'iterable(mixed[])',
				'$iterableWithIterableTypehint',
			],
			[
				'mixed',
				'$iterableWithIterableTypehint[0]',
			],
			[
				'mixed',
				'$mixed',
			],
			[
				'iterable(Iterables\Bar[])',
				'$iterableWithConcreteTypehint',
			],
			[
				'mixed',
				'$iterableWithConcreteTypehint[0]',
			],
			[
				'Iterables\Bar',
				'$bar',
			],
			[
				'iterable(mixed[])',
				'$this->doBar()',
			],
			[
				'iterable(Iterables\Baz[])',
				'$this->doBaz()',
			],
			[
				'Iterables\Baz',
				'$baz',
			],
			[
				'mixed[]',
				'$arrayWithIterableTypehint',
			],
			[
				'mixed',
				'$arrayWithIterableTypehint[0]',
			],
			[
				'Iterables\Bar[]|Iterables\Collection',
				'$unionIterableType',
			],
			[
				'Iterables\Bar',
				'$unionBar',
			],
			[
				'Iterables\Bar[]|Iterables\Collection|Iterables\Foo[]',
				'$mixedUnionIterableType',
			],
			[
				'Iterables\Bar[]|Iterables\Collection',
				'$unionIterableIterableType',
			],
			[
				'mixed',
				'$mixedBar',
			],
			[
				'Iterables\Bar',
				'$iterableUnionBar',
			],
			[
				'Iterables\Bar',
				'$unionBarFromMethod',
			],
			[
				'iterable(string[])',
				'$this->stringIterableProperty',
			],
			[
				'iterable(mixed[])',
				'$this->mixedIterableProperty',
			],
			[
				'iterable(int[])',
				'$integers',
			],
			[
				'iterable(mixed[])',
				'$mixeds',
			],
			[
				'iterable(mixed[])',
				'$this->returnIterableMixed()',
			],
			[
				'iterable(string[])',
				'$this->returnIterableString()',
			],
			[
				'int|iterable(string[])',
				'$this->iterablePropertyAlsoWithSomethingElse',
			],
			[
				'int|iterable(int[]|string[])',
				'$this->iterablePropertyWithTwoItemTypes',
			],
		];
	}

	/**
	 * @requires PHP 7.1
	 * @dataProvider dataIterable
	 * @param string $description
	 * @param string $expression
	 */
	public function testIterable(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/iterable.php',
			$description,
			$expression
		);
	}

	public function dataVoid(): array
	{
		return [
			[
				'void',
				'$this->doFoo()',
			],
			[
				'void',
				'$this->doBar()',
			],
			[
				'void',
				'$this->doConflictingVoid()',
			],
		];
	}

	/**
	 * @requires PHP 7.1
	 * @dataProvider dataVoid
	 * @param string $description
	 * @param string $expression
	 */
	public function testVoid(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/void.php',
			$description,
			$expression
		);
	}

	public function dataNullableReturnTypes(): array
	{
		return [
			[
				'int|null',
				'$this->doFoo()',
			],
			[
				'int|null',
				'$this->doBar()',
			],
			[
				'int|null',
				'$this->doConflictingNullable()',
			],
			[
				'int',
				'$this->doAnotherConflictingNullable()',
			],
		];
	}

	/**
	 * @requires PHP 7.1
	 * @dataProvider dataNullableReturnTypes
	 * @param string $description
	 * @param string $expression
	 */
	public function testNullableReturnTypes(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/nullable-returnTypes.php',
			$description,
			$expression
		);
	}

	public function dataTernary(): array
	{
		return [
			[
				'bool|null',
				'$boolOrNull',
			],
			[
				'bool',
				'$boolOrNull !== null ? $boolOrNull : false',
			],
			[
				'bool',
				'$bool',
			],
		];
	}

	/**
	 * @dataProvider dataTernary
	 * @param string $description
	 * @param string $expression
	 */
	public function testTernary(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/ternary.php',
			$description,
			$expression
		);
	}

	public function dataHeredoc(): array
	{
		return [
			[
				'string',
				'$heredoc',
			],
			[
				'string',
				'$nowdoc',
			],
		];
	}

	/**
	 * @dataProvider dataHeredoc
	 * @param string $description
	 * @param string $expression
	 */
	public function testHeredoc(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/heredoc.php',
			$description,
			$expression
		);
	}

	public function dataTypeElimination(): array
	{
		return [
			[
				'null',
				'$foo',
				"'nullForSure';",
			],
			[
				'TypeElimination\Foo',
				'$foo',
				"'notNullForSure';",
			],
			[
				'TypeElimination\Foo',
				'$foo',
				"'notNullForSure2';",
			],
			[
				'null',
				'$foo',
				"'nullForSure2';",
			],
			[
				'null',
				'$foo',
				"'nullForSure3';",
			],
			[
				'TypeElimination\Foo',
				'$foo',
				"'notNullForSure3';",
			],
			[
				'null',
				'$foo',
				"'yodaNullForSure';",
			],
			[
				'TypeElimination\Foo',
				'$foo',
				"'yodaNotNullForSure';",
			],
			[
				'false',
				'$intOrFalse',
				"'falseForSure';",
			],
			[
				'int',
				'$intOrFalse',
				"'intForSure';",
			],
			[
				'false',
				'$intOrFalse',
				"'yodaFalseForSure';",
			],
			[
				'int',
				'$intOrFalse',
				"'yodaIntForSure';",
			],
			[
				'true',
				'$intOrTrue',
				"'trueForSure';",
			],
			[
				'int',
				'$intOrTrue',
				"'anotherIntForSure';",
			],
			[
				'true',
				'$intOrTrue',
				"'yodaTrueForSure';",
			],
			[
				'int',
				'$intOrTrue',
				"'yodaAnotherIntForSure';",
			],
			[
				'TypeElimination\Foo',
				'$fooOrBarOrBaz',
				"'fooForSure';",
			],
			[
				'TypeElimination\Bar|TypeElimination\Baz',
				'$fooOrBarOrBaz',
				"'barOrBazForSure';",
			],
			[
				'TypeElimination\Bar',
				'$fooOrBarOrBaz',
				"'barForSure';",
			],
			[
				'TypeElimination\Baz',
				'$fooOrBarOrBaz',
				"'bazForSure';",
			],
			[
				'TypeElimination\Bar|TypeElimination\Baz',
				'$fooOrBarOrBaz',
				"'anotherBarOrBazForSure';",
			],
			[
				'TypeElimination\Foo',
				'$fooOrBarOrBaz',
				"'anotherFooForSure';",
			],
			[
				'string|null',
				'$result',
				"'stringOrNullForSure';",
			],
			[
				'int',
				'$intOrFalse',
				"'yetAnotherIntForSure';",
			],
			[
				'int',
				'$intOrTrue',
				"'yetYetAnotherIntForSure';",
			],
			[
				'TypeElimination\Foo|null',
				'$fooOrStringOrNull',
				"'fooOrNull';",
			],
			[
				'string',
				'$fooOrStringOrNull',
				"'stringForSure';",
			],
			[
				'string',
				'$fooOrStringOrNull',
				"'anotherStringForSure';",
			],
			[
				'null',
				'$this->bar',
				"'propertyNullForSure';",
			],
			[
				'TypeElimination\Bar',
				'$this->bar',
				"'propertyNotNullForSure';",
			],
		];
	}

	/**
	 * @dataProvider dataTypeElimination
	 * @param string $description
	 * @param string $expression
	 * @param string $evaluatedPointExpression
	 */
	public function testTypeElimination(
		string $description,
		string $expression,
		string $evaluatedPointExpression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/type-elimination.php',
			$description,
			$expression,
			[],
			[],
			$evaluatedPointExpression
		);
	}

	public function dataMisleadingTypes(): array
	{
		return [
			[
				'MisleadingTypes\boolean',
				'$foo->misleadingBoolReturnType()',
			],
			[
				'MisleadingTypes\integer',
				'$foo->misleadingIntReturnType()',
			],
			[
				'MisleadingTypes\mixed',
				'$foo->misleadingMixedReturnType()',
			],
		];
	}

	/**
	 * @dataProvider dataMisleadingTypes
	 * @param string $description
	 * @param string $expression
	 */
	public function testMisleadingTypes(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/misleading-types.php',
			$description,
			$expression
		);
	}

	public function dataMisleadingTypesWithoutNamespace(): array
	{
		return [
			[
				'boolean', // would have been "bool" for a real boolean
				'$foo->misleadingBoolReturnType()',
			],
			[
				'integer',
				'$foo->misleadingIntReturnType()',
			],
		];
	}

	/**
	 * @dataProvider dataMisleadingTypesWithoutNamespace
	 * @param string $description
	 * @param string $expression
	 */
	public function testMisleadingTypesWithoutNamespace(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/misleading-types-without-namespace.php',
			$description,
			$expression
		);
	}

	public function dataCombineTypes(): array
	{
		return [
			[
				'string|null',
				'$x',
			],
			[
				'int|null',
				'$y',
			],
		];
	}

	/**
	 * @dataProvider dataCombineTypes
	 * @param string $description
	 * @param string $expression
	 */
	public function testCombineTypes(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/combine-types.php',
			$description,
			$expression
		);
	}

	public function dataConstants(): array
	{
		return [
			[
				'int',
				'$foo',
			],
			[
				'mixed',
				'NONEXISTENT_CONSTANT',
			],
		];
	}

	/**
	 * @dataProvider dataConstants
	 * @param string $description
	 * @param string $expression
	 */
	public function testConstants(
		string $description,
		string $expression
	)
	{
		if (!defined('ConstantsForNodeScopeResolverTest\\FOO_CONSTANT')) {
			define('ConstantsForNodeScopeResolverTest\\FOO_CONSTANT', 1);
		}
		$this->assertTypes(
			__DIR__ . '/data/constants.php',
			$description,
			$expression
		);
	}

	public function dataFinally(): array
	{
		return [
			[
				'int|string',
				'$integerOrString',
			],
			[
				'BarException|FooException|null',
				'$fooOrBarException',
			],
		];
	}

	/**
	 * @dataProvider dataFinally
	 * @param string $description
	 * @param string $expression
	 */
	public function testFinally(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/finally.php',
			$description,
			$expression
		);
	}

	/**
	 * @dataProvider dataFinally
	 * @param string $description
	 * @param string $expression
	 */
	public function testFinallyWithEarlyTermination(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/finally-with-early-termination.php',
			$description,
			$expression
		);
	}

	public function dataInheritDocFromInterface(): array
	{
		return [
			[
				'string',
				'$string',
			],
		];
	}

	/**
	 * @dataProvider dataInheritDocFromInterface
	 * @param string $description
	 * @param string $expression
	 */
	public function testInheritDocFromInterface(
		string $description,
		string $expression
	)
	{
		$this->assertTypes(
			__DIR__ . '/data/inheritdoc-from-interface.php',
			$description,
			$expression
		);
	}

	private function assertTypes(
		string $file,
		string $description,
		string $expression,
		array $dynamicMethodReturnTypeExtensions = [],
		array $dynamicStaticMethodReturnTypeExtensions = [],
		string $evaluatedPointExpression = 'die;'
	)
	{
		$this->processFile($file, function (\PhpParser\Node $node, Scope $scope) use ($description, $expression, $evaluatedPointExpression) {
			$printedNode = $this->printer->prettyPrint([$node]);
			if ($printedNode === $evaluatedPointExpression) {
				/** @var \PhpParser\Node\Expr $expressionNode */
				$expressionNode = $this->getParser()->parseString(sprintf('<?php %s;', $expression))[0];
				$type = $scope->getType($expressionNode);
				$this->assertTypeDescribe(
					$description,
					$type->describe(),
					sprintf('%s at %s', $expression, $evaluatedPointExpression)
				);
			}
		}, $dynamicMethodReturnTypeExtensions, $dynamicStaticMethodReturnTypeExtensions);
	}

	private function processFile(string $file, \Closure $callback, array $dynamicMethodReturnTypeExtensions = [], array $dynamicStaticMethodReturnTypeExtensions = [])
	{
		$this->resolver->processNodes(
			$this->getParser()->parseFile($file),
			new Scope(
				$this->createBroker($dynamicMethodReturnTypeExtensions, $dynamicStaticMethodReturnTypeExtensions),
				$this->printer,
				new TypeSpecifier($this->printer),
				$file
			),
			$callback
		);
	}

	public function dataDeclareStrictTypes(): array
	{
		return [
			[
				__DIR__ . '/data/declareWeakTypes.php',
				false,
			],
			[
				__DIR__ . '/data/noDeclare.php',
				false,
			],
			[
				__DIR__ . '/data/declareStrictTypes.php',
				true,
			],
		];
	}

	/**
	 * @dataProvider dataDeclareStrictTypes
	 * @param string $file
	 * @param bool $result
	 */
	public function testDeclareStrictTypes(string $file, bool $result)
	{
		$this->processFile($file, function (\PhpParser\Node $node, Scope $scope) use ($result) {
			if ($node instanceof Exit_) {
				$this->assertSame($result, $scope->isDeclareStrictTypes());
			}
		});
	}

	public function testEarlyTermination()
	{
		$this->processFile(__DIR__ . '/data/early-termination.php', function (\PhpParser\Node $node, Scope $scope) {
			if ($node instanceof Exit_) {
				$this->assertTrue($scope->hasVariableType('something')->yes());
				$this->assertTrue($scope->hasVariableType('var')->yes());
			}
		});
	}

	private function assertTypeDescribe(string $expectedDescription, string $actualDescription, string $label = '')
	{
		$this->assertEquals(
			$expectedDescription,
			$actualDescription,
			$label
		);
	}

}
