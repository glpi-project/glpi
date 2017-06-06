/**
 * Return all elements of `array` that have a fuzzy match against `pattern`.
 */
export declare function simpleFilter(
  pattern: string,
  array: string[]
): string[];

/**
 * Does `pattern` fuzzy match `inputString`?
 */
export declare function test(
  pattern: string,
  inputString: string
): boolean;

export interface MatchOptions {
  pre?: string;
  post?: string;
  caseSensitive?: boolean;
}

export interface MatchResult {
  rendered: string;
  score: number;
}

/**
 * If `pattern` matches `inputString`, wrap each matching character in `opts.pre`
 * and `opts.post`. If no match, return null.
 */
export declare function match(
  pattern: string,
  inputString: string,
  opts?: MatchOptions
): MatchResult;

export interface FilterOptions<T> {
  pre?: string;
  post?: string;
  extract?(input: T): string;
}

export interface FilterResult<T> {
  string: string;
  score: number;
  index: number;
  original: T;
}

/**
 * The normal entry point. Filters `arr` for matches against `pattern`.
 */
export declare function filter<T>(
  pattern: string,
  arr: T[],
  opts?: FilterOptions<T>
): FilterResult<T>[];
