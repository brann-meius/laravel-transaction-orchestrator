# Contribution Guide

Thank you for your interest in improving **Laravel Transaction Orchestrator**!
Every contribution - makes a difference.

## How to Contribute

1. **Report Issues**
   Use [GitHub Issues](https://github.com/brann-meius/laravel-transaction-orchestrator/issues) to report bugs or suggest features.
   Please include:

    * A clear description of the problem or idea
    * Steps to reproduce (if applicable)
    * Your environment (PHP version, OS, etc.)

2. **Make Changes**

    * Fork the repository
    * Clone your fork and install dependencies:

      ```bash
      git clone https://github.com/{YOUR-USERNAME}/laravel-transaction-orchestrator.git
      cd laravel-transaction-orchestrator
      composer install
      ```
      
    * Create a feature branch:

      ```bash
      git checkout -b feature/{FEATURE_NAME}
      ```
      
    * Write clean, tested, and documented code
    * Commit with clear messages
    * Push your branch and open a Pull Request

## Guidelines

* **Code Style**: Follow **PSR-12**. Run checks with:

  ```bash
  composer cs
  ```
  
* **Tests**: All new code must include tests. Run them with:

  ```bash
  composer test
  ```
  
* **Docs**: Update the README or related docs if behavior changes.
* **Pull Requests**: Keep each PR focused on a single feature or fix.

## Principles

* Keep it simple, clear, and maintainable
* Prioritize improvements that serve many users
* Respect the project’s scope - big ideas may be better as separate packages
