<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UacsCode extends Model
{
    protected $table = 'uacscodes';
    
    protected $fillable = [
        'parent_id',
        'uacscode',
        'description',
        'level',
        'is_selectable',
        'sort_order',
    ];

    protected $casts = [
        'is_selectable' => 'boolean',
        'level' => 'integer',
        'sort_order' => 'integer',
    ];

    // ========================
    // RELATIONSHIPS
    // ========================
    
    public function parent()
    {
        return $this->belongsTo(UACSCode::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(UACSCode::class, 'parent_id')
                    ->orderBy('sort_order')
                    ->orderBy('uacscode');
    }

    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * ORS Requests using this UACS code with amount
     */
    public function orsRequests()
    {
        return $this->belongsToMany(ORSRequest::class, 'ors_request_uacscode')
                    ->withPivot('order', 'amount')
                    ->withTimestamps();
    }

    // ========================
    // ACCESSORS
    // ========================
    
    /**
     * Get full hierarchical path
     */
    public function getFullPathAttribute()
    {
        $path = collect([$this->description]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent->description);
            $parent = $parent->parent;
        }
        
        return $path->implode(' → ');
    }

    /**
     * Get formatted display name with code
     */
    public function getDisplayNameAttribute()
    {
        return "{$this->uacscode} - {$this->description}";
    }

    /**
     * Get indented display for dropdowns
     */
    public function getIndentedDisplayAttribute()
    {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $this->level);
        return $indent . $this->display_name;
    }

    /**
     * Get amount for specific ORS request (from pivot)
     */
    public function getAmountForOrs($orsRequestId)
    {
        $pivot = $this->orsRequests()
                     ->where('ors_request_id', $orsRequestId)
                     ->first();
        
        return $pivot ? $pivot->pivot->amount : 0;
    }

    // ========================
    // QUERY SCOPES
    // ========================
    
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')
                     ->orderBy('sort_order')
                     ->orderBy('uacscode');
    }

    public function scopeSelectable($query)
    {
        return $query->where('is_selectable', true)
                     ->orderBy('sort_order')
                     ->orderBy('uacscode');
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level)
                     ->orderBy('sort_order')
                     ->orderBy('uacscode');
    }

    public function scopeChildrenOf($query, $parentId)
    {
        return $query->where('parent_id', $parentId)
                     ->orderBy('sort_order')
                     ->orderBy('uacscode');
    }

    // ========================
    // HELPER METHODS
    // ========================
    
    public function isLeaf()
    {
        return $this->children()->count() === 0;
    }

    public function isRoot()
    {
        return $this->parent_id === null;
    }

    public function getAncestorIds()
    {
        $ids = collect();
        $parent = $this->parent;
        
        while ($parent) {
            $ids->push($parent->id);
            $parent = $parent->parent;
        }
        
        return $ids;
    }

    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }
        
        return $ancestors->reverse();
    }

    public function getBreadcrumb()
    {
        return $this->ancestors()
                    ->push($this)
                    ->map(fn($item) => [
                        'id' => $item->id,
                        'code' => $item->uacscode,
                        'name' => $item->description,
                        'level' => $item->level,
                    ])
                    ->toArray();
    }

    public function getDescendantIds()
    {
        $ids = collect([$this->id]);
        
        foreach ($this->children as $child) {
            $ids = $ids->merge($child->getDescendantIds());
        }
        
        return $ids;
    }

    // ========================
    // STATIC HELPER METHODS
    // ========================
    
    public static function buildTree($items = null, $parentId = null)
    {
        if ($items === null) {
            $items = static::orderBy('sort_order')->orderBy('uacscode')->get();
        }
        
        $tree = [];
        
        foreach ($items as $item) {
            if ($item->parent_id == $parentId) {
                $children = static::buildTree($items, $item->id);
                
                $node = [
                    'id' => $item->id,
                    'code' => $item->uacscode,
                    'description' => $item->description,
                    'level' => $item->level,
                    'is_selectable' => $item->is_selectable,
                    'children' => $children
                ];
                
                $tree[] = $node;
            }
        }
        
        return $tree;
    }

    public static function getFlattenedList($parentId = null, $level = 0)
    {
        $list = collect();
        
        $items = static::where('parent_id', $parentId)
                       ->orderBy('sort_order')
                       ->orderBy('uacscode')
                       ->get();
        
        foreach ($items as $item) {
            $item->level = $level;
            $list->push($item);
            
            $children = static::getFlattenedList($item->id, $level + 1);
            $list = $list->merge($children);
        }
        
        return $list;
    }

    /**
     * Get total amount for this UACS code across all ORS requests
     */
    public function getTotalAmount()
    {
        return $this->orsRequests()->sum('ors_request_uacscode.amount');
    }

    /**
     * Get ORS requests with amounts
     */
    public function getOrsWithAmounts()
    {
        return $this->orsRequests()
                    ->select('ors_requests.*', 'ors_request_uacscode.amount as uacs_amount')
                    ->get();
    }
}