const { test, expect } = require('@playwright/test');

test.describe('Document Workflow E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('complete document workflow', async ({ page }) => {
    // Navigate to documents
    await page.click('text=Documents');
    await page.waitForURL('/documents');
    
    // Upload a document
    await page.click('text=Upload Document');
    await page.waitForURL('/documents/create');
    
    await page.fill('input[name="title"]', 'Test Document E2E');
    await page.fill('textarea[name="description"]', 'End-to-end test document');
    await page.selectOption('select[name="assigned_to_type"]', 'user');
    
    // Wait for users to load and select MD user
    await page.waitForTimeout(1000);
    await page.selectOption('select[name="assigned_to_id"]', { label: /Medical Director/ });
    await page.selectOption('select[name="priority"]', 'high');
    
    // Upload file (mock)
    const fileInput = page.locator('input[name="file"]');
    await fileInput.setInputFiles({
      name: 'test.pdf',
      mimeType: 'application/pdf',
      buffer: Buffer.from('Mock PDF content')
    });
    
    await page.click('button[type="submit"]');
    await page.waitForURL('/documents');
    
    // Verify document appears in list
    await expect(page.locator('text=Test Document E2E')).toBeVisible();
    
    // Click on the document to view it
    await page.click('text=Test Document E2E');
    
    // Add a minute
    await page.click('text=Add Minute');
    await page.fill('textarea[name="body"]', 'This document needs urgent review.');
    await page.selectOption('select[name="visibility"]', 'public');
    
    // Forward to procurement
    await page.selectOption('select[name="forwarded_to_type"]', 'user');
    await page.waitForTimeout(500);
    await page.selectOption('select[name="forwarded_to_id"]', { label: /Procurement Officer/ });
    
    await page.click('button[type="submit"]');
    
    // Verify minute appears
    await expect(page.locator('text=This document needs urgent review.')).toBeVisible();
    await expect(page.locator('text=Forwarded to Procurement Officer')).toBeVisible();
    
    // Test export functionality
    await page.click('text=Export PDF');
    
    // Verify download initiated (check for PDF response)
    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.click('text=Export PDF')
    ]);
    
    expect(download.suggestedFilename()).toContain('.pdf');
  });

  test('search functionality', async ({ page }) => {
    await page.goto('/search');
    
    await page.fill('input[name="q"]', 'test');
    await page.click('button[type="submit"]');
    
    // Should show search results (even if empty in test environment)
    await expect(page.locator('.search-results')).toBeVisible();
  });

  test('admin can manage users', async ({ page }) => {
    await page.goto('/admin/dashboard');
    
    await page.click('text=Users');
    
    // Should see user management interface
    await expect(page.locator('text=User Management')).toBeVisible();
    await expect(page.locator('text=admin@example.com')).toBeVisible();
    await expect(page.locator('text=md@example.com')).toBeVisible();
  });

  test('user permissions are enforced', async ({ page }) => {
    // Logout admin
    await page.click('button:has-text("System Administrator")');
    await page.click('text=Log Out');
    
    // Login as regular user (procurement officer)
    await page.goto('/login');
    await page.fill('input[name="email"]', 'proc@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    
    // Should not see admin menu
    await expect(page.locator('text=Admin')).not.toBeVisible();
    
    // Should not be able to access admin routes
    await page.goto('/admin/dashboard');
    await expect(page.locator('text=403')).toBeVisible();
  });

  test('responsive design works on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 }); // iPhone SE size
    
    await page.goto('/documents');
    
    // Mobile menu should be visible
    await expect(page.locator('button[aria-label="Menu"]')).toBeVisible();
    
    // Desktop navigation should be hidden
    await expect(page.locator('nav .hidden.sm\\:flex')).not.toBeVisible();
    
    // Cards should stack vertically
    const documentCards = page.locator('.grid > div');
    const firstCard = documentCards.first();
    const secondCard = documentCards.nth(1);
    
    if (await documentCards.count() > 1) {
      const firstBox = await firstCard.boundingBox();
      const secondBox = await secondCard.boundingBox();
      
      // On mobile, cards should stack vertically (second card below first)
      expect(secondBox.y).toBeGreaterThan(firstBox.y + firstBox.height);
    }
  });
});