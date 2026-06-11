<?php

return [
    // 基本验证规则
    'required'             => ':attribute 不能为空',
    'required_if'          => ':attribute 不能为空',
    'required_unless'      => ':attribute 不能为空',
    'required_with'        => ':attribute 不能为空',
    'required_with_all'    => ':attribute 不能为空',
    'required_without'     => ':attribute 不能为空',
    'required_without_all' => ':attribute 不能为空',

    // 类型验证
    'string'   => ':attribute 必须是字符串',
    'array'    => ':attribute 必须是数组',
    'boolean'  => ':attribute 必须是布尔值',
    'integer'  => ':attribute 必须是整数',
    'numeric'  => ':attribute 必须是数字',
    'file'     => ':attribute 必须是文件',
    'image'    => ':attribute 必须是图片',

    // 格式验证
    'email'         => ':attribute 必须是有效的邮箱地址',
    'url'           => ':attribute 必须是有效的 URL',
    'date'          => ':attribute 必须是有效的日期',
    'date_format'   => ':attribute 必须符合格式 :format',
    'alpha'         => ':attribute 只能包含字母',
    'alpha_dash'    => ':attribute 只能包含字母、数字、短划线和下划线',
    'alpha_num'     => ':attribute 只能包含字母和数字',
    'regex'         => ':attribute 格式不正确',
    'timezone'      => ':attribute 必须是有效的时区',

    // 长度/大小验证
    'min' => [
        'string'  => ':attribute 不能少于 :min 个字符',
        'numeric' => ':attribute 不能小于 :min',
        'array'   => ':attribute 不能少于 :min 个元素',
    ],
    'max' => [
        'string'  => ':attribute 不能超过 :max 个字符',
        'numeric' => ':attribute 不能大于 :max',
        'array'   => ':attribute 不能超过 :max 个元素',
    ],
    'between' => [
        'string'  => ':attribute 必须在 :min 到 :max 个字符之间',
        'numeric' => ':attribute 必须在 :min 到 :max 之间',
        'array'   => ':attribute 必须在 :min 到 :max 个元素之间',
    ],
    'size' => [
        'string'  => ':attribute 必须是 :size 个字符',
        'numeric' => ':attribute 必须是 :size',
        'array'   => ':attribute 必须包含 :size 个元素',
    ],

    // 范围验证
    'in'        => ':attribute 必须是以下值之一：:values',
    'not_in'    => ':attribute 不能是以下值之一：:values',
    'exists'    => ':attribute 不存在',
    'unique'    => ':attribute 已存在',
    'distinct'  => ':attribute 有重复值',

    // 关系验证
    'confirmed' => ':attribute 确认不匹配',
    'same'      => ':attribute 和 :other 必须相同',
    'different' => ':attribute 和 :other 必须不同',

    // 日期验证
    'before'        => ':attribute 必须在 :date 之前',
    'after'         => ':attribute 必须在 :date 之后',
    'date_between'  => ':attribute 必须在 :min 和 :max 之间',

    // 文件验证
    'mimes'      => ':attribute 必须是以下类型：:values',
    'mimetypes'  => ':attribute 必须是以下 MIME 类型：:values',
    'file_size'  => ':attribute 不能超过 :size KB',
    'dimensions' => ':attribute 图片尺寸不符合要求',

    // 密码验证
    'password'          => '密码不正确',
    'current_password'  => '当前密码不正确',

    // 其他
    'nullable' => ':attribute 可以为空',
];
