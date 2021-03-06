<?php

/**
 * @see       https://github.com/laminas/laminas-cli for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cli/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cli/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Cli\Input;

use InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use function sprintf;

// phpcs:disable WebimpressCodingStandard.Commenting.TagWithType.InvalidTypeFormat
// phpcs:disable WebimpressCodingStandard.Commenting.TagWithType.InvalidParamName
/**
 * Decorate an input instance to add a `getParam()` method.
 *
 * @internal
 */
abstract class AbstractParamAwareInput implements ParamAwareInputInterface
{
    /** @var QuestionHelper */
    protected $helper;

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var array<string, InputParamInterface> */
    private $params;

    /**
     * @param array<string, InputParamInterface> $params
     */
    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $helper, array $params)
    {
        $this->input  = $input;
        $this->output = $output;
        $this->helper = $helper;
        $this->params = $params;
    }

    /**
     * Define this method in order to modify the question, if needed, before
     * prompting for an answer.
     */
    abstract protected function modifyQuestion(Question $question): void;

    /**
     * @return mixed
     * @throws InvalidArgumentException When the parameter does not exist.
     * @throws InvalidArgumentException When the parameter is of an invalid type.
     * @throws InvalidArgumentException When the parameter is required, input is
     *     non-interactive, and no value is provided.
     */
    final public function getParam(string $name)
    {
        if (! isset($this->params[$name])) {
            throw new InvalidArgumentException(sprintf('Invalid parameter name: %s', $name));
        }

        $value      = $this->input->getOption($name);
        $inputParam = $this->params[$name];

        if (! $inputParam instanceof InputParamInterface) {
            throw new InvalidArgumentException(sprintf(
                'Invalid parameter type; must be of type %s',
                InputParamInterface::class
            ));
        }

        $question = $inputParam->getQuestion();

        $this->modifyQuestion($question);

        if ($value === null && ! $this->input->isInteractive()) {
            $value = $inputParam->getDefault();
        }

        if ($value !== null) {
            $validator = $question->getValidator();
            if ($validator) {
                $validator($value);
            }

            $normalizer = $question->getNormalizer();

            return $normalizer === null ? $value : $normalizer($value);
        }

        if (! $this->input->isInteractive() && $inputParam->isRequired()) {
            throw new InvalidArgumentException(sprintf('Missing required value for --%s parameter', $name));
        }

        $value = $this->helper->ask($this, $this->output, $question);

        // set the option value so it can be reused in chains
        $this->input->setOption($name, $value);

        return $value;
    }

    // Proxy methods implementing interface (common across symfony/console versions)
    // phpcs:disable WebimpressCodingStandard.Functions.Param.MissingSpecification, WebimpressCodingStandard.Functions.ReturnType.ReturnValue

    public function getFirstArgument()
    {
        return $this->input->getFirstArgument();
    }

    public function bind(InputDefinition $definition)
    {
        $this->input->bind($definition);
    }

    public function validate()
    {
        $this->input->validate();
    }

    public function getArguments()
    {
        return $this->input->getArguments();
    }

    public function hasArgument($name)
    {
        return $this->input->hasArgument($name);
    }

    public function getOptions()
    {
        return $this->input->getOptions();
    }

    public function isInteractive()
    {
        return $this->input->isInteractive();
    }

    public function setStream($stream)
    {
        if (! $this->input instanceof StreamableInputInterface) {
            return;
        }
        $this->input->setStream($stream);
    }

    public function getStream()
    {
        if (! $this->input instanceof StreamableInputInterface) {
            return null;
        }
        return $this->input->getStream();
    }

    // phpcs:enable
}
