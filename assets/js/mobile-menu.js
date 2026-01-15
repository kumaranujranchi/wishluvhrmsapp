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
            e.stopImmediatePropagation();
            console.log('🖱️ Menu clicked!');
            
            const isActive = drawer.classList.contains('active');
            
            if (isActive) {
                // Close drawer
                drawer.classList.remove('active');
                overlay.classList.remove('active');
                drawer.style.right = '-280px';
                console.log('❌ Closing drawer');
            } else {
                // Open drawer
                drawer.classList.add('active');
                overlay.classList.add('active');
                drawer.style.right = '0px';
                console.log('✅ Opening drawer');
                
                // Attach overlay click handler AFTER a small delay
                setTimeout(function() {
                    overlay.addEventListener('click', function closeDrawer(e) {
                        e.stopPropagation();
                        console.log('🖱️ Overlay clicked - closing drawer');
                        drawer.classList.remove('active');
                        overlay.classList.remove('active');
                        drawer.style.right = '-280px';
                        overlay.removeEventListener('click', closeDrawer);
                    }, { once: true });
                }, 100);
            }
            
            console.log('Drawer active:', drawer.classList.contains('active'));
            console.log('Drawer right position:', drawer.style.right);
        });
        
        console.log('✅ Menu handlers attached successfully');
    } else {
        console.error('❌ Missing elements!');
    }
});
