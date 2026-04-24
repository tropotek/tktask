#!/usr/bin/env bash
#
# Entrypoint script for tk projects
#
set -e

# Function to create a symbolic link if the target directory does not exist
create_public_link() {
    local target_dir="public"

    # Check if the 'public' directory exists
    if [ ! -d "$target_dir" ]; then
        echo "Directory '$target_dir' does not exist. Creating symbolic link..."

        # Create a symbolic link to the current directory
        ln -s . "$target_dir"

        if [ $? -eq 0 ]; then
            echo "Symbolic link created successfully."
        else
            echo "Failed to create symbolic link."
            exit 1
        fi
    else
        echo "Directory '$target_dir' already exists. No action taken."
    fi
}
# Call the function to create the symbolic link
create_public_link


echo "If this is your project initial setup run 'composer install' to get started:"
# composer install --no-interaction --prefer-dist --optimize-autoloader


# Execute the CMD from the Dockerfile/Compose
exec "$@"