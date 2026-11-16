<?php

namespace App\Models\Water;

/** Cart stockable "water_truck": Clean water delivered by truck to an estate (refill_price, else sale_price). */
class WaterTruck extends Inventory
{
    public const OPTION = 'truck';
}
