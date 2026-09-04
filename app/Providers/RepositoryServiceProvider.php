<?php

namespace App\Providers;

use App\Repositories\Contracts\MemberRepositoryInterface;
use App\Repositories\Contracts\PickupRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\TrashCategoryRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\MemberRepository;
use App\Repositories\Eloquent\PickupRepository;
use App\Repositories\Eloquent\TransactionRepository;
use App\Repositories\Eloquent\TrashCategoryRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Binding contract interface repository ke implementasi Eloquent.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        MemberRepositoryInterface::class => MemberRepository::class,
        TrashCategoryRepositoryInterface::class => TrashCategoryRepository::class,
        PickupRepositoryInterface::class => PickupRepository::class,
        TransactionRepositoryInterface::class => TransactionRepository::class,
    ];
}
