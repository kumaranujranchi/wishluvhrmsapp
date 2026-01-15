// Mobile Menu Drawer Handler
console.log('📱 Mobile menu handler loaded');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Setting up mobile menu');
    
    const menuBtn = document.getElementById('mobileMenuBtn');
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('drawerOverlay');
    
    console.log('Elements check:');
    console.log('- Menu button:', menuBtn);
    console.log('- Drawer:', drawer);
    console.log('- Overlay:', overlay);
    
    if (menuBtn && drawer && overlay) {
        // Remove any existing listeners
        const newMenuBtn = menuBtn.cloneNode(true);
        menuBtn.parentNode.replaceChild(newMenuBtn, menuBtn);
        
        // Add fresh click listener
        newMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🖱️ Menu clicked!');
            
            drawer.classList.toggle('active');
            overlay.classList.toggle('active');
            
            console.log('✅ Toggled - Drawer active:', drawer.classList.contains('active'));
        });
        
        // Also handle overlay click to close
        overlay.addEventListener('click', function() {
            console.log('🖱️ Overlay clicked - closing drawer');
            drawer.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        console.log('✅ Menu handlers attached successfully');
    } else {
        console.error('❌ Missing elements!');
    }
});
