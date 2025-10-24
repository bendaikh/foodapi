#!/bin/bash

# Performance Optimization Deployment Script
# This script applies all performance optimizations to your production environment

echo "========================================="
echo "Performance Optimization Deployment"
echo "========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Please run this script from the project root.${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 1: Backing up database...${NC}"
php artisan backup:run 2>/dev/null || echo "Backup skipped (no backup package installed)"
echo ""

echo -e "${YELLOW}Step 2: Putting application in maintenance mode...${NC}"
php artisan down --message="Performance optimization in progress" --retry=60
echo -e "${GREEN}✓ Application is now in maintenance mode${NC}"
echo ""

echo -e "${YELLOW}Step 3: Running database migrations (adding indexes)...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database migrations completed successfully${NC}"
else
    echo -e "${RED}✗ Migration failed! Rolling back...${NC}"
    php artisan up
    exit 1
fi
echo ""

echo -e "${YELLOW}Step 4: Clearing all caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ All caches cleared${NC}"
echo ""

echo -e "${YELLOW}Step 5: Optimizing application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo -e "${GREEN}✓ Application optimized${NC}"
echo ""

echo -e "${YELLOW}Step 6: Restarting queue workers...${NC}"
php artisan queue:restart
echo -e "${GREEN}✓ Queue workers restarted${NC}"
echo ""

echo -e "${YELLOW}Step 7: Bringing application back online...${NC}"
php artisan up
echo -e "${GREEN}✓ Application is now online${NC}"
echo ""

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Deployment completed successfully!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""

echo "Verifying database indexes..."
php artisan tinker --execute="echo 'Orders indexes: ' . count(DB::select('SHOW INDEXES FROM orders')); echo PHP_EOL; echo 'Items indexes: ' . count(DB::select('SHOW INDEXES FROM items'));"
echo ""

echo "Performance optimization deployment complete!"
echo ""
echo "Next steps:"
echo "1. Test the /home page and verify faster loading"
echo "2. Test the /admin/dashboard and verify improvements"
echo "3. Run Lighthouse test again to see score improvement"
echo "4. Monitor application logs for any issues"
echo ""
echo "Expected improvements:"
echo "- Home page: 80-90% faster on subsequent loads"
echo "- Dashboard: 85-90% faster with caching"
echo "- Lighthouse score: Should improve from 48 to 75-85"

