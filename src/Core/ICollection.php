<?php
namespace PHPTools\Core;

use Countable;

/**
 * @template T
 */
interface ICollection extends Countable {
    public int $length { get; }

    /**
     * Undocumented function
     *
     * @param callable(T): bool $predicate
     * @return ICollection<T>
     */
    public function where(callable $predicate): ICollection;

    /**
     * Undocumented function
     *
     * @template TResult
     * @param callable(T): TResult $selector
     * @return ICollection<TResult>
     */
    public function select(callable $selector): ICollection;

    /**
     * Undocumented function
     * @template TComparer
     * @param callable(T): TComparer $comparer
     * @return ICollection<T>
     */
    public function orderBy(callable $comparer): ICollection;

    /**
     * @param T $value
     * @return boolean
     */
    public function contains(mixed $value): bool;

    /**
     * Undocumented function
     *
     * @return T|null
     */
    public function last(): mixed;

     /**
     * Undocumented function
     *
     * @return T|null
     */
    public function first(): mixed;

    /**
     * Undocumented function
     *
     * @return T[]
     */
    public function toArray(): array;
}