export default async function run(page, ui) {
  await ui.click('@e5'); // Escritorio
  await page.waitForTimeout(3000);
  return await ui.snapshot({ full: true });
}