#!/bin/bash
# @file
# Drupal-8 environment variables.

# Override the install profile for Drupal 8.
# This is needed as behat does not work with minimal right now.
export DRUPAL_TI_INSTALL_PROFILE="standard";
