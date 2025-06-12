#!/bin/bash

# Start Jekyll development server for League Container docs
# This script sets up the proper Ruby path and starts the server
# Note: No Gemfile is used to maintain GitHub Pages compatibility

export PATH="/usr/local/opt/ruby/bin:/usr/local/lib/ruby/gems/3.4.0/bin:$PATH"

cd "$(dirname "$0")"

echo "🚀 Starting Jekyll development server..."
echo "📍 Site will be available at: http://localhost:4001"
echo "✨ Live reload enabled - changes will be reflected automatically"
echo "⏹️  Press Ctrl+C to stop the server"
echo ""

# Use Jekyll directly (no bundle) since we removed Gemfile for GitHub Pages compatibility
jekyll serve --port 4001 --livereload
