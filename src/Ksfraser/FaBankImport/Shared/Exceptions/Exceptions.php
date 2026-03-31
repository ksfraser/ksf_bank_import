<?php
namespace Ksfraser\FaBankImport\Shared\Exceptions;

use Exception;

/**
 * BaseKsfException - Base exception for Shared Kernel
 * 
 * @package Ksfraser\FaBankImport\Shared\Exceptions
 * @stable
 */
class BaseKsfException extends Exception
{
}

/**
 * InvalidTransactionException - Thrown when transaction invariants are violated
 */
final class InvalidTransactionException extends BaseKsfException
{
}

/**
 * InvalidStatementException - Thrown when statement invariants are violated
 */
final class InvalidStatementException extends BaseKsfException
{
}

/**
 * RepositoryException - Base exception for repository operations
 */
class RepositoryException extends BaseKsfException
{
}

/**
 * EntityNotFoundException - Thrown when entity not found
 */
final class EntityNotFoundException extends RepositoryException
{
}

/**
 * DuplicateEntityException - Thrown when duplicate entity detected
 */
final class DuplicateEntityException extends RepositoryException
{
}

/**
 * InvalidRepositoryStateException - Thrown for invalid repository state
 */
final class InvalidRepositoryStateException extends RepositoryException
{
}

/**
 * ConfigurationException - Thrown for configuration errors
 */
final class ConfigurationException extends BaseKsfException
{
}

/**
 * ModuleBootstrapException - Thrown during module initialization
 */
final class ModuleBootstrapException extends BaseKsfException
{
}

/**
 * ContainerException - Thrown by DI container
 */
class ContainerException extends BaseKsfException
{
}

/**
 * ServiceNotFoundException - Thrown when service not found in container
 */
final class ServiceNotFoundException extends ContainerException
{
}
