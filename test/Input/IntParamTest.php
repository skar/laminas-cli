<?php

/**
 * @see       https://github.com/laminas/laminas-cli for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cli/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cli/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Cli\Input;

use Laminas\Cli\Input\IntParam;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;

use const PHP_EOL;

class IntParamTest extends TestCase
{
    /** @var IntParam */
    private $param;

    public function setUp(): void
    {
        $this->param = new IntParam('test');
        $this->param->setDescription('A number');
    }

    public function testUsesValueRequiredOptionMode(): void
    {
        $this->assertSame(InputOption::VALUE_REQUIRED, $this->param->getOptionMode());
    }

    public function defaultValues(): iterable
    {
        $question = '<question>A number:</question>';
        $suffix   = PHP_EOL . ' > ';

        yield 'null' => [null, $question . $suffix];
        yield 'integer' => [1, $question . ' [<comment>1</comment>]' . $suffix];
    }

    /**
     * @dataProvider defaultValues
     */
    public function testCreatesStandardQuestionUsingDefaultValue(
        ?int $default,
        string $expectedQuestionText
    ): void {
        $this->param->setDefault($default);
        $question = $this->param->getQuestion();
        $this->assertEquals($expectedQuestionText, $question->getQuestion());
    }

    public function testQuestionContainsANormalizer(): void
    {
        $normalizer = $this->param->getQuestion()->getNormalizer();
        $this->assertIsCallable($normalizer);
    }

    public function numericInput(): iterable
    {
        yield 'string zero'    => ['0', 0];
        yield 'string integer' => ['1', 1];
        yield 'integer'        => [1, 1];
    }

    /**
     * @dataProvider numericInput
     * @param mixed $value
     */
    public function testNormalizerCastsNumericValuesToIntegers($value, int $expected): void
    {
        $normalizer = $this->param->getQuestion()->getNormalizer();
        $this->assertSame($expected, $normalizer($value));
    }

    public function nonNumericInput(): iterable
    {
        yield 'string'              => ['string'];
        yield 'string float zero'   => ['0.0'];
        yield 'string float'        => ['1.1'];
        yield 'float'               => [1.1];
    }

    /**
     * @dataProvider nonNumericInput
     * @param mixed $value
     */
    public function testNormalizerDoesNotCastNonNumericValues($value): void
    {
        $normalizer = $this->param->getQuestion()->getNormalizer();
        $this->assertSame($value, $normalizer($value));
    }

    public function testQuestionContainsAValidator(): void
    {
        $validator = $this->param->getQuestion()->getValidator();
        $this->assertIsCallable($validator);
    }

    public function testValidatorReturnsNullIfValueIsNullAndParamIsNotRequired(): void
    {
        $validator = $this->param->getQuestion()->getValidator();
        $this->assertNull($validator(null));
    }

    public function testValidatorRaisesExceptionIfValueIsNullAndRequired(): void
    {
        $this->param->setRequiredFlag(true);
        $validator = $this->param->getQuestion()->getValidator();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid value: integer expected');
        $validator(null);
    }

    /**
     * @dataProvider nonNumericInput
     * @param mixed $value
     */
    public function testValidatorRaisesExceptionIfRequiredAndNonNumeric($value): void
    {
        $this->param->setRequiredFlag(true);
        $validator = $this->param->getQuestion()->getValidator();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid value: integer expected');
        $validator($value);
    }

    public function testValidatorRaisesExceptionIfRequiredAndBelowMinimum(): void
    {
        $this->param->setRequiredFlag(true);
        $this->param->setMin(10);
        $validator = $this->param->getQuestion()->getValidator();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid value 1; minimum value is 10');
        $validator(1);
    }

    public function testValidatorRaisesExceptionIfRequiredAndAboveMaximum(): void
    {
        $this->param->setRequiredFlag(true);
        $this->param->setMax(10);
        $validator = $this->param->getQuestion()->getValidator();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid value 100; maximum value is 10');
        $validator(100);
    }

    public function testValidatorReturnsValueVerbatimIfValueIsValid(): void
    {
        $this->param->setRequiredFlag(true);
        $this->param->setMin(1);
        $this->param->setMax(10);
        $validator = $this->param->getQuestion()->getValidator();

        $this->assertSame(5, $validator(5));
    }
}
