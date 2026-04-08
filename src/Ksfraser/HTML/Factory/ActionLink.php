<?php
namespace Ksfraser\HTML\Factory;

use Ksfraser\HTML\Elements\HtmlA;

/**
 * ActionLink - Specialized link for action-based URLs
 * 
 * Extends HtmlA to provide convenient methods for common CRUD action links.
 * Automatically constructs query string URLs with action parameter.
 * 
 * Common actions: create, edit, update, delete, view, list, admin
 * 
 * SRP: Single responsibility of building action-based links
 * 
 * @package Ksfraser\HTML\Elements
 */
class ActionLink extends HtmlA {
    /**
     * Set action parameter for URL
     * 
     * @param string $action Action name (create, edit, delete, etc.)
     * @param array $params Additional query parameters
     * @return self
     */
    public function setAction(string $action, array $params = []): self {
        $queryParams = array_merge(['action' => $action], $params);
        $queryString = http_build_query($queryParams);
        $this->setHref('?' . $queryString);
        return $this;
    }
    
    /**
     * Create a "Create" action link
     * 
     * @param string $text Link text (default: "Create")
     * @return self
     */
    public static function create(string $text = 'Create'): self {
        return (new self())
            ->setAction('create')
            ->setText($text);
    }
    
    /**
     * Create an "Edit" action link
     * 
     * @param int|string $id Entity ID
     * @param string $text Link text (default: "Edit")
     * @return self
     */
    public static function edit($id, string $text = 'Edit'): self {
        return (new self())
            ->setAction('edit', ['id' => $id])
            ->setText($text);
    }
    
    /**
     * Create a "View" action link
     * 
     * @param int|string $id Entity ID
     * @param string $text Link text (default: "View")
     * @return self
     */
    public static function view($id, string $text = 'View'): self {
        return (new self())
            ->setAction('view', ['id' => $id])
            ->setText($text);
    }
    
    /**
     * Create a "Delete" action link
     * 
     * @param int|string $id Entity ID
     * @param string $text Link text (default: "Delete")
     * @return self
     */
    public static function delete($id, string $text = 'Delete'): self {
        return (new self())
            ->setAction('delete', ['id' => $id])
            ->setText($text)
            ->setAttribute('onclick', 'return confirm("Are you sure you want to delete this item?");');
    }
    
    /**
     * Create a "List" action link
     * 
     * @param string $text Link text (default: "List")
     * @return self
     */
    public static function listAll(string $text = 'List'): self {
        return (new self())
            ->setAction('list')
            ->setText($text);
    }
    
    /**
     * Create an "Admin" action link
     * 
     * @param string $subAction Optional sub-action
     * @param string $text Link text (default: "Admin")
     * @return self
     */
    public static function admin(string $subAction = '', string $text = 'Admin'): self {
        $link = (new self())->setText($text);
        
        if ($subAction) {
            $link->setAction('admin_' . $subAction);
        } else {
            $link->setAction('admin');
        }
        
        return $link;
    }
    
    /**
     * Create a custom action link
     * 
     * @param string $action Custom action name
     * @param string $text Link text
     * @param array $params Additional parameters
     * @return self
     */
    public static function custom(string $action, string $text, array $params = []): self {
        return (new self())
            ->setAction($action, $params)
            ->setText($text);
    }
    /**
     * Set the label for this add link
     * 
     * @param string $label
     * @return self
     */
    public function setLabel(string $label): self {
        $this->label = $label;
        $this->setText($label);
        return $this;
    }
    /**
     * Get the current label
     * 
     * @return string
     */
    public function getLabel(): string {
        return $this->label;
    }
}
