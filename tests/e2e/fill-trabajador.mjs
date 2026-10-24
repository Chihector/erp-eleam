export default async function run(page, ui) {
  await ui.fill('@e19', '19486694-1'); // RUT
  await ui.fill('@e21', 'María'); // Nombres
  await ui.fill('@e22', 'González'); // Apellidos
  await ui.fill('@e23', 'Cuidadora'); // Cargo
  await ui.fill('@e20', '2024-03-15'); // Fecha Ingreso
  await ui.fill('@e24', '#FF6B6B'); // Color
  
  // Select AFP
  await ui.click('@e25');
  await page.waitForTimeout(500);
  await page.keyboard.type('Modelo');
  await page.keyboard.press('Enter');
  await page.waitForTimeout(500);
  
  // Select Sistema de Salud
  await ui.click('@e27');
  await page.waitForTimeout(500);
  await page.keyboard.type('Fonasa');
  await page.keyboard.press('Enter');
  await page.waitForTimeout(500);
  
  await ui.click('@e28'); // Crear
  await page.waitForTimeout(3000);
  return await ui.snapshot();
}