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
    
    /**
     * Get the parent UACS code
     */
    public function parent()
    {
        return $this->belongsTo(UACSCode::class, 'parent_id');
    }

    /**
     * Get all children UACS codes
     */
    public function children()
    {
        return $this->hasMany(UACSCode::class, 'parent_id')
                    ->orderBy('sort_order')
                    ->orderBy('uacscode');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * ORS Requests using this UACS code
     */
    public function orsRequests()
    {
        return $this->belongsToMany(ORSRequest::class, 'ors_request_uacscode')
                    ->withPivot('order')
                    ->withTimestamps();
    }

    // ========================
    // ACCESSORS
    // ========================
    
    /**
     * Get full hierarchical path
     * Example: "Expenses → Financial Assistance/Subsidy → Subsidies - Other"
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

    // ========================
    // QUERY SCOPES
    // ========================
    
    /**
     * Get only root nodes (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')
                     ->orderBy('sort_order')
                     ->orderBy('uacscode');
    }

    /**
     * Get only selectable items (usually leaf nodes)
     */
    public function scopeSelectable($query)
    {
        return $query->where('is_selectable', true)
                     ->orderBy('sort_order')
                     ->orderBy('uacscode');
    }

    /**
     * Get by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level)
                     ->orderBy('sort_order')
                     ->orderBy('uacscode');
    }

    /**
     * Get children of specific parent
     */
    public function scopeChildrenOf($query, $parentId)
    {
        return $query->where('parent_id', $parentId)
                     ->orderBy('sort_order')
                     ->orderBy('uacscode');
    }

    // ========================
    // HELPER METHODS
    // ========================
    
    /**
     * Check if this is a leaf node (no children)
     */
    public function isLeaf()
    {
        return $this->children()->count() === 0;
    }

    /**
     * Check if this is a root node (no parent)
     */
    public function isRoot()
    {
        return $this->parent_id === null;
    }

    /**
     * Get all ancestor IDs (for breadcrumb trails)
     */
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

    /**
     * Get all ancestors (for breadcrumb)
     */
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

    /**
     * Get breadcrumb array
     */
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

    /**
     * Get all descendant IDs
     */
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
    
    /**
     * Build hierarchical tree structure
     */
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

    /**
     * Get flattened list with proper indentation
     */
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
}
