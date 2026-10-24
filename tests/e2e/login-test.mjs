export default async function run(page, ui) {
  // Fill login form
  await ui.fill('@e1', 'admin@eleam.cl');
  await ui.fill('@e2', 'password');
  await ui.click('@e6');
  await page.waitForTimeout(3000);
  
  // Take snapshot after login
  return await ui.snapshot();
}