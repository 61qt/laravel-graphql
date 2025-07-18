<?php

declare(strict_types=1);

namespace QT\GraphQL\Traits;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;

trait Validate
{
    /**
     * 对input内容校验规则
     *
     * @var array
     */
    protected $rules = [];

    /**
     * 校验失败后提示信息
     *
     * @var array
     */
    protected $messages = [];

    /**
     * 校验失败后字段名称
     *
     * @var array
     */
    protected $customAttributes = [];

    /**
     * 校验器生成工厂
     *
     * @var Factory
     */
    protected $factory;

    /**
     * 核对输入的参数类型
     *
     * @param array|Collection $input
     * @param array $rules
     * @param array $message
     * @param array $customAttributes
     * @throws Error
     * @return array
     */
    public function validate(
        array|Collection $input,
        array $rules = [],
        array $message = [],
        array $customAttributes = []
    ): array {
        if ($input instanceof Collection) {
            $input = $input->toArray();
        }

        $rules            = $rules ?: $this->rules;
        $message          = $message ?: $this->messages;
        $customAttributes = $customAttributes ?: $this->customAttributes;
        $validator        = $this->getValidationFactory()->make(
            $input,
            $rules,
            $message,
            $this->customAttributes
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * @return Factory
     */
    public function getValidationFactory(): Factory
    {
        return $this->factory;
    }

    /**
     * @param Factory $factory
     */
    public function setValidationFactory(Factory $factory)
    {
        $this->factory = $factory;
    }
}
