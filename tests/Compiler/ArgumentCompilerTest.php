<?php

declare(strict_types=1);

use League\Container\Argument\DefaultValueArgument;
use League\Container\Argument\Literal\ArrayArgument;
use League\Container\Argument\Literal\BooleanArgument;
use League\Container\Argument\Literal\CallableArgument;
use League\Container\Argument\Literal\FloatArgument;
use League\Container\Argument\Literal\IntegerArgument;
use League\Container\Argument\Literal\ObjectArgument;
use League\Container\Argument\Literal\StringArgument;
use League\Container\Argument\LiteralArgument;
use League\Container\Argument\ResolvableArgument;
use League\Container\Compiler\ArgumentCompiler;
use League\Container\Compiler\CompilationException;
use League\Container\Test\Asset\Foo;

test('string argument produces single-quoted literal', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new StringArgument('hello'), []))->toBe("'hello'");
});

test('string argument with single quote escapes correctly', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new StringArgument("it's"), []))->toBe("'it\\'s'");
});

test('string argument with backslash escapes correctly', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new StringArgument('a\\b'), []))->toBe("'a\\\\b'");
});

test('integer argument produces var export representation', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new IntegerArgument(42), []))->toBe('42');
});

test('negative integer argument produces var export representation', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new IntegerArgument(-7), []))->toBe('-7');
});

test('float argument produces var export representation', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new FloatArgument(3.14), []))->toBe('3.14');
});

test('boolean true argument produces true literal', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new BooleanArgument(true), []))->toBe('true');
});

test('boolean false argument produces false literal', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new BooleanArgument(false), []))->toBe('false');
});

test('array argument produces var export representation', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new ArrayArgument([1, 'two', true]), []);

    expect($result)->toBe(var_export([1, 'two', true], true));
});

test('array argument with nested data produces var export representation', function () {
    $compiler = new ArgumentCompiler();

    $nested = ['key' => ['a', 'b'], 'flag' => false];
    $result = $compiler->compile(new ArrayArgument($nested), []);

    expect($result)->toBe(var_export($nested, true));
});

test('resolvable argument produces get call with service id', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new ResolvableArgument('App\\Service'), []))->toBe("\$this->get('App\\\\Service')");
});

test('default value argument resolves via get when service is known', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new DefaultValueArgument('App\\Service', 'fallback'), ['App\\Service']);

    expect($result)->toBe("\$this->get('App\\\\Service')");
});

test('default value argument uses var export of default when service is not known', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new DefaultValueArgument('App\\Service', 'fallback'), []);

    expect($result)->toBe("'fallback'");
});

test('default value argument with null default produces null when service is not known', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new DefaultValueArgument('App\\Service', null), []);

    expect($result)->toBe('null');
});

test('default value argument with integer default produces integer when service is not known', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new DefaultValueArgument('App\\Service', 99), []);

    expect($result)->toBe('99');
});

test('callable argument with array callable produces class constant and method expression', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new CallableArgument([Foo::class, 'staticSetBar']), []);

    expect($result)->toBe("[\\League\\Container\\Test\\Asset\\Foo::class, 'staticSetBar']");
});

test('callable argument with fully qualified class strips leading backslash', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new CallableArgument(['\\League\\Container\\Test\\Asset\\Foo', 'staticSetBar']), []);

    expect($result)->toBe("[\\League\\Container\\Test\\Asset\\Foo::class, 'staticSetBar']");
});

test('callable argument wrapping a closure throws compilation exception', function () {
    $compiler = new ArgumentCompiler();

    expect(fn() => $compiler->compile(new CallableArgument(static fn() => null), []))
        ->toThrow(CompilationException::class);
});

test('callable argument closure exception includes closure error type', function () {
    $compiler = new ArgumentCompiler();

    try {
        $compiler->compile(new CallableArgument(static fn() => null), [], 'App\\Service', 1);
        expect(false)->toBeTrue();
    } catch (CompilationException $e) {
        $errors = $e->getErrors();
        expect($errors)->toHaveCount(1);
        expect($errors[0]['errorType'])->toBe('closure_argument');
        expect($errors[0]['serviceId'])->toBe('App\\Service');
        expect($errors[0]['message'])->toContain('position 1');
    }
});

test('callable argument closure exception without position omits position from message', function () {
    $compiler = new ArgumentCompiler();

    try {
        $compiler->compile(new CallableArgument(static fn() => null), []);
        expect(false)->toBeTrue();
    } catch (CompilationException $e) {
        $errors = $e->getErrors();
        expect($errors[0]['message'])->not->toContain('position');
    }
});

test('callable argument closure exception includes suggested fix', function () {
    $compiler = new ArgumentCompiler();

    try {
        $compiler->compile(new CallableArgument(static fn() => null), []);
        expect(false)->toBeTrue();
    } catch (CompilationException $e) {
        $errors = $e->getErrors();
        expect($errors[0]['suggestedFix'])->not->toBeEmpty();
    }
});

test('callable argument closure exception defaults service id to unknown when not provided', function () {
    $compiler = new ArgumentCompiler();

    try {
        $compiler->compile(new CallableArgument(static fn() => null), []);
        expect(false)->toBeTrue();
    } catch (CompilationException $e) {
        $errors = $e->getErrors();
        expect($errors[0]['serviceId'])->toBe('unknown');
    }
});

test('object argument always throws compilation exception', function () {
    $compiler = new ArgumentCompiler();

    expect(fn() => $compiler->compile(new ObjectArgument(new stdClass()), []))
        ->toThrow(CompilationException::class);
});

test('object argument exception includes object error type', function () {
    $compiler = new ArgumentCompiler();

    try {
        $compiler->compile(new ObjectArgument(new stdClass()), [], 'App\\Service', 0);
        expect(false)->toBeTrue();
    } catch (CompilationException $e) {
        $errors = $e->getErrors();
        expect($errors)->toHaveCount(1);
        expect($errors[0]['errorType'])->toBe('object_argument');
        expect($errors[0]['serviceId'])->toBe('App\\Service');
    }
});

test('object argument exception message names the object class', function () {
    $compiler = new ArgumentCompiler();

    try {
        $compiler->compile(new ObjectArgument(new stdClass()), []);
        expect(false)->toBeTrue();
    } catch (CompilationException $e) {
        $errors = $e->getErrors();
        expect($errors[0]['message'])->toContain('stdClass');
    }
});

test('object argument exception includes argument position in message when provided', function () {
    $compiler = new ArgumentCompiler();

    try {
        $compiler->compile(new ObjectArgument(new stdClass()), [], 'App\\Service', 2);
        expect(false)->toBeTrue();
    } catch (CompilationException $e) {
        $errors = $e->getErrors();
        expect($errors[0]['message'])->toContain('position 2');
    }
});

test('object argument exception includes suggested fix referencing the class', function () {
    $compiler = new ArgumentCompiler();

    try {
        $compiler->compile(new ObjectArgument(new stdClass()), []);
        expect(false)->toBeTrue();
    } catch (CompilationException $e) {
        $errors = $e->getErrors();
        expect($errors[0]['suggestedFix'])->toContain('stdClass');
    }
});

test('object argument exception defaults service id to unknown when not provided', function () {
    $compiler = new ArgumentCompiler();

    try {
        $compiler->compile(new ObjectArgument(new stdClass()), []);
        expect(false)->toBeTrue();
    } catch (CompilationException $e) {
        $errors = $e->getErrors();
        expect($errors[0]['serviceId'])->toBe('unknown');
    }
});

test('raw string matching known service produces get call', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile('App\\Service', ['App\\Service']))->toBe("\$this->get('App\\\\Service')");
});

test('raw string not matching any known service produces quoted literal', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile('some-value', ['App\\Service']))->toBe("'some-value'");
});

test('raw string with no known services produces quoted literal', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile('hello', []))->toBe("'hello'");
});

test('raw integer produces var export representation', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(100, []))->toBe('100');
});

test('raw float produces var export representation', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(1.5, []))->toBe('1.5');
});

test('raw boolean true produces true literal', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(true, []))->toBe('true');
});

test('raw boolean false produces false literal', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(false, []))->toBe('false');
});

test('raw null produces null literal', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(null, []))->toBe('null');
});

test('resolvable argument service id with special characters is properly quoted', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new ResolvableArgument("it's-a-service"), []);

    expect($result)->toBe("\$this->get('it\\'s-a-service')");
});

test('raw string matching one of multiple known services produces get call', function () {
    $compiler = new ArgumentCompiler();

    $knownServices = ['App\\First', 'App\\Second', 'App\\Third'];
    $result = $compiler->compile('App\\Second', $knownServices);

    expect($result)->toBe("\$this->get('App\\\\Second')");
});

test('default value argument checks exact match in known services', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new DefaultValueArgument('App\\Service', 'fallback'), ['App\\OtherService']);

    expect($result)->toBe("'fallback'");
});

test('literal argument with null value produces null literal', function () {
    $compiler = new ArgumentCompiler();

    expect($compiler->compile(new LiteralArgument(null), []))->toBe('null');
});

test('callable argument with string function name produces quoted literal', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(new CallableArgument('strlen'), []);

    expect($result)->toBe("'strlen'");
});

test('raw array produces var export representation', function () {
    $compiler = new ArgumentCompiler();

    $result = $compiler->compile(['key' => 'value', 'num' => 42], []);

    expect($result)->toBe(var_export(['key' => 'value', 'num' => 42], true));
});
