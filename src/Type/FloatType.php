<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;

class FloatType implements Type
{

	/**
	 * @return string|null
	 */
	public function getClass()
	{
		return null;
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [];
	}

	public function combineWith(Type $otherType): Type
	{
		if ($otherType instanceof $this) {
			return new self();
		}

		return TypeCombinator::combine($this, $otherType);
	}

	public function accepts(Type $type): bool
	{
		if ($type instanceof self || $type instanceof IntegerType) {
			return true;
		}

		if ($type instanceof CompoundType) {
			return CompoundTypeHelper::accepts($type, $this);
		}

		return false;
	}

	public function describe(): string
	{
		return 'float';
	}

	public function canAccessProperties(): bool
	{
		return false;
	}

	public function canCallMethods(): bool
	{
		return false;
	}

	public function isDocumentableNatively(): bool
	{
		return true;
	}

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getIterableKeyType(): Type
	{
		return new ErrorType();
	}

	public function getIterableValueType(): Type
	{
		return new ErrorType();
	}

	public static function __set_state(array $properties): Type
	{
		return new self();
	}

}
