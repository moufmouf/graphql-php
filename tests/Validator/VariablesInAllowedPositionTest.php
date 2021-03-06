<?php

declare(strict_types=1);

namespace GraphQL\Tests\Validator;

use GraphQL\Error\FormattedError;
use GraphQL\Language\SourceLocation;
use GraphQL\Validator\Rules\VariablesInAllowedPosition;

class VariablesInAllowedPositionTest extends ValidatorTestCase
{
    // Validate: Variables are in allowed positions
    /**
     * @see it('Boolean => Boolean')
     */
    public function testBooleanXBoolean() : void
    {
        // Boolean => Boolean
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($booleanArg: Boolean)
      {
        complicatedArgs {
          booleanArgField(booleanArg: $booleanArg)
        }
      }
        '
        );
    }

    /**
     * @see it('Boolean => Boolean within fragment')
     */
    public function testBooleanXBooleanWithinFragment() : void
    {
        // Boolean => Boolean within fragment
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      fragment booleanArgFrag on ComplicatedArgs {
        booleanArgField(booleanArg: $booleanArg)
      }
      query Query($booleanArg: Boolean)
      {
        complicatedArgs {
          ...booleanArgFrag
        }
      }
        '
        );

        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($booleanArg: Boolean)
      {
        complicatedArgs {
          ...booleanArgFrag
        }
      }
      fragment booleanArgFrag on ComplicatedArgs {
        booleanArgField(booleanArg: $booleanArg)
      }
        '
        );
    }

    /**
     * @see it('Boolean! => Boolean')
     */
    public function testBooleanNonNullXBoolean() : void
    {
        // Boolean! => Boolean
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($nonNullBooleanArg: Boolean!)
      {
        complicatedArgs {
          booleanArgField(booleanArg: $nonNullBooleanArg)
        }
      }
        '
        );
    }

    /**
     * @see it('Boolean! => Boolean within fragment')
     */
    public function testBooleanNonNullXBooleanWithinFragment() : void
    {
        // Boolean! => Boolean within fragment
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      fragment booleanArgFrag on ComplicatedArgs {
        booleanArgField(booleanArg: $nonNullBooleanArg)
      }

      query Query($nonNullBooleanArg: Boolean!)
      {
        complicatedArgs {
          ...booleanArgFrag
        }
      }
        '
        );
    }

    /**
     * @see it('Int => Int! with default')
     */
    public function testIntXIntNonNullWithDefault() : void
    {
        // Int => Int! with default
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($intArg: Int = 1)
      {
        complicatedArgs {
          nonNullIntArgField(nonNullIntArg: $intArg)
        }
      }
        '
        );
    }

    /**
     * @see it('[String] => [String]')
     */
    public function testListOfStringXListOfString() : void
    {
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($stringListVar: [String])
      {
        complicatedArgs {
          stringListArgField(stringListArg: $stringListVar)
        }
      }
        '
        );
    }

    /**
     * @see it('[String!] => [String]')
     */
    public function testListOfStringNonNullXListOfString() : void
    {
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($stringListVar: [String!])
      {
        complicatedArgs {
          stringListArgField(stringListArg: $stringListVar)
        }
      }
        '
        );
    }

    /**
     * @see it('String => [String] in item position')
     */
    public function testStringXListOfStringInItemPosition() : void
    {
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($stringVar: String)
      {
        complicatedArgs {
          stringListArgField(stringListArg: [$stringVar])
        }
      }
        '
        );
    }

    /**
     * @see it('String! => [String] in item position')
     */
    public function testStringNonNullXListOfStringInItemPosition() : void
    {
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($stringVar: String!)
      {
        complicatedArgs {
          stringListArgField(stringListArg: [$stringVar])
        }
      }
        '
        );
    }

    /**
     * @see it('ComplexInput => ComplexInput')
     */
    public function testComplexInputXComplexInput() : void
    {
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($complexVar: ComplexInput)
      {
        complicatedArgs {
          complexArgField(complexArg: $ComplexInput)
        }
      }
        '
        );
    }

    /**
     * @see it('ComplexInput => ComplexInput in field position')
     */
    public function testComplexInputXComplexInputInFieldPosition() : void
    {
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($boolVar: Boolean = false)
      {
        complicatedArgs {
          complexArgField(complexArg: {requiredArg: $boolVar})
        }
      }
        '
        );
    }

    /**
     * @see it('Boolean! => Boolean! in directive')
     */
    public function testBooleanNonNullXBooleanNonNullInDirective() : void
    {
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($boolVar: Boolean!)
      {
        dog @include(if: $boolVar)
      }
        '
        );
    }

    /**
     * @see it('Boolean => Boolean! in directive with default')
     */
    public function testBooleanXBooleanNonNullInDirectiveWithDefault() : void
    {
        $this->expectPassesRule(
            new VariablesInAllowedPosition(),
            '
      query Query($boolVar: Boolean = false)
      {
        dog @include(if: $boolVar)
      }
        '
        );
    }

    /**
     * @see it('Int => Int!')
     */
    public function testIntXIntNonNull() : void
    {
        $this->expectFailsRule(
            new VariablesInAllowedPosition(),
            '
      query Query($intArg: Int) {
        complicatedArgs {
          nonNullIntArgField(nonNullIntArg: $intArg)
        }
      }
        ',
            [
                FormattedError::create(
                    VariablesInAllowedPosition::badVarPosMessage('intArg', 'Int', 'Int!'),
                    [new SourceLocation(2, 19), new SourceLocation(4, 45)]
                ),
            ]
        );
    }

    /**
     * @see it('Int => Int! within fragment')
     */
    public function testIntXIntNonNullWithinFragment() : void
    {
        $this->expectFailsRule(
            new VariablesInAllowedPosition(),
            '
      fragment nonNullIntArgFieldFrag on ComplicatedArgs {
        nonNullIntArgField(nonNullIntArg: $intArg)
      }

      query Query($intArg: Int) {
        complicatedArgs {
          ...nonNullIntArgFieldFrag
        }
      }
        ',
            [
                FormattedError::create(
                    VariablesInAllowedPosition::badVarPosMessage('intArg', 'Int', 'Int!'),
                    [new SourceLocation(6, 19), new SourceLocation(3, 43)]
                ),
            ]
        );
    }

    /**
     * @see it('Int => Int! within nested fragment')
     */
    public function testIntXIntNonNullWithinNestedFragment() : void
    {
        // Int => Int! within nested fragment
        $this->expectFailsRule(
            new VariablesInAllowedPosition(),
            '
      fragment outerFrag on ComplicatedArgs {
        ...nonNullIntArgFieldFrag
      }

      fragment nonNullIntArgFieldFrag on ComplicatedArgs {
        nonNullIntArgField(nonNullIntArg: $intArg)
      }

      query Query($intArg: Int)
      {
        complicatedArgs {
          ...outerFrag
        }
      }
        ',
            [
                FormattedError::create(
                    VariablesInAllowedPosition::badVarPosMessage('intArg', 'Int', 'Int!'),
                    [new SourceLocation(10, 19), new SourceLocation(7, 43)]
                ),
            ]
        );
    }

    /**
     * @see it('String over Boolean')
     */
    public function testStringOverBoolean() : void
    {
        $this->expectFailsRule(
            new VariablesInAllowedPosition(),
            '
      query Query($stringVar: String) {
        complicatedArgs {
          booleanArgField(booleanArg: $stringVar)
        }
      }
        ',
            [
                FormattedError::create(
                    VariablesInAllowedPosition::badVarPosMessage('stringVar', 'String', 'Boolean'),
                    [new SourceLocation(2, 19), new SourceLocation(4, 39)]
                ),
            ]
        );
    }

    /**
     * @see it('String => [String]')
     */
    public function testStringXListOfString() : void
    {
        $this->expectFailsRule(
            new VariablesInAllowedPosition(),
            '
      query Query($stringVar: String) {
        complicatedArgs {
          stringListArgField(stringListArg: $stringVar)
        }
      }
        ',
            [
                FormattedError::create(
                    VariablesInAllowedPosition::badVarPosMessage('stringVar', 'String', '[String]'),
                    [new SourceLocation(2, 19), new SourceLocation(4, 45)]
                ),
            ]
        );
    }

    /**
     * @see it('Boolean => Boolean! in directive')
     */
    public function testBooleanXBooleanNonNullInDirective() : void
    {
        $this->expectFailsRule(
            new VariablesInAllowedPosition(),
            '
      query Query($boolVar: Boolean) {
        dog @include(if: $boolVar)
      }
        ',
            [
                FormattedError::create(
                    VariablesInAllowedPosition::badVarPosMessage('boolVar', 'Boolean', 'Boolean!'),
                    [new SourceLocation(2, 19), new SourceLocation(3, 26)]
                ),
            ]
        );
    }

    /**
     * @see it('String => Boolean! in directive')
     */
    public function testStringXBooleanNonNullInDirective() : void
    {
        // String => Boolean! in directive
        $this->expectFailsRule(
            new VariablesInAllowedPosition(),
            '
      query Query($stringVar: String) {
        dog @include(if: $stringVar)
      }
        ',
            [
                FormattedError::create(
                    VariablesInAllowedPosition::badVarPosMessage('stringVar', 'String', 'Boolean!'),
                    [new SourceLocation(2, 19), new SourceLocation(3, 26)]
                ),
            ]
        );
    }

    /**
     * @see it('[String] => [String!]')
     */
    public function testStringArrayXStringNonNullArray() : void
    {
        $this->expectFailsRule(
            new VariablesInAllowedPosition(),
            '
      query Query($stringListVar: [String])
      {
        complicatedArgs {
          stringListNonNullArgField(stringListNonNullArg: $stringListVar)
        }
      }
        ',
            [
                FormattedError::create(
                    VariablesInAllowedPosition::badVarPosMessage('stringListVar', '[String]', '[String!]'),
                    [new SourceLocation(2, 19), new SourceLocation(5, 59)]
                ),
            ]
        );
    }
}
