<?php

namespace App\Controllers;

use Exception;

/**
 * @url /account
 * @author 刘文岳
 * @date 2017-12-28
 * @version 1.0.0
 * @phone 18695616095
 */
class Account {
    /**
     * @param token string? 授权
     */
    public function __construct($token = null)
    {
    }

    /**
     * @url GET /login
     * @param name string 名称
     * @param password string 密码
     * @return array{
     *              name: string 名称,
     *              age: array[array{
     *                  text: string 名称,
     *                  value: int 值
     *              }] 年龄,
     *              password: string 密码
     *          } 返回数据
     */
    public function login($name, $password)
    {
        return ['age' => 1, 'name' => $name, 'password' => $password];
    }

    public function register()
    {
        # code...
    }

    public function changePassword()
    {
        # code...
    }

    public function destory()
    {
        # code...
    }
}
