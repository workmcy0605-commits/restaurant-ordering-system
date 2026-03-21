<?php

namespace App;

class Constant
{
    // === Order Status ===
    const ADD_TO_CART = 'ADD_TO_CART';

    const CREATED = 'CREATED';

    const PENDING = 'PENDING';

    const APPROVED = 'APPROVED';

    const PROCESSING = 'PROCESSING';

    const COOKING = 'COOKING';

    const ON_DELIVERY = 'ON_DELIVERY';

    const READY = 'READY';

    const COMPLETED = 'COMPLETED';

    const CANCELLED = 'CANCELLED';

    const REJECTED = 'REJECTED';

    const PENDING_VERIFICATION = 'PENDING_VERIFICATION';

    const FAIL = 'FAIL';

    const SCANNED = 'SCANNED';

    const OVERTIME = 'OVERTIME';

    const REFUND = 'REFUND';

    const EXPIRED = 'EXPIRED';

    // === Order Item Status ===
    const ITEM_PENDING = 'PENDING';

    const ITEM_APPROVED = 'APPROVED';

    const ITEM_COOKING = 'COOKING';

    const ITEM_READY = 'READY';

    const ITEM_ON_DELIVERY = 'ON_DELIVERY';

    const ITEM_COMPLETED = 'COMPLETED';

    const ITEM_REJECTED = 'REJECTED';

    const ITEM_CANCELLED = 'CANCELLED';

    // === Menu Item Import Min Set ===
    const SINGLE = 'SINGLE';

    const MULTIPLE = 'MULTIPLE';

    // === Grouping ===
    const ORDER_STATUS = [

        self::CREATED,
        self::PENDING,
        self::APPROVED,
        self::PROCESSING,
        self::COOKING,
        self::ON_DELIVERY,
        self::COMPLETED,
        self::CANCELLED,
        self::REJECTED,
        self::FAIL,
        self::OVERTIME,
    ];

    const ORDER_ITEM_STATUS = [
        self::ITEM_PENDING,
        self::ITEM_APPROVED,
        self::ITEM_COOKING,
        self::ITEM_READY,
        self::ITEM_ON_DELIVERY,
        self::ITEM_COMPLETED,
        self::ITEM_REJECTED,
        self::ITEM_CANCELLED,
    ];

    const USER_CLASS = 'App\Models\User';
}
