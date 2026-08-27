#!/usr/bin/env python3

import requests
import json
import time

def test_page_content(page_name, expected_content):
    """Test if a specific page has real content"""
    print(f"\n=== Testing {page_name} Page ===")
    
    # Get dashboard HTML
    response = requests.get('http://localhost:8888/', timeout=10)
    html = response.text
    
    # Check if page exists in HTML
    if f'id="{page_name}"' not in html:
        print(f"❌ FAILED: {page_name} page not found in HTML")
        return False
    
    # Extract page content
    start_marker = f'<!-- {page_name.title()} Page -->'
    end_marker = f'<!-- {"Images" if page_name == "images" else "Compose" if page_name == "compose" else "Monitoring" if page_name == "monitoring" else "Settings" if page_name == "settings" else page_name.title()} Page -->'
    
    start_pos = html.find(start_marker)
    end_pos = html.find(end_marker)
    
    if start_pos == -1 or end_pos == -1:
        print(f"❌ FAILED: Page markers not found for {page_name}")
        return False
    
    page_content = html[start_pos + len(start_marker):end_pos]
    
    # Check if it has loading text
    if 'Loading' in page_content:
        print(f"❌ FAILED: {page_name} page shows 'Loading...' instead of real content")
        print(f"Content preview: {page_content[:200]}...")
        return False
    
    # Check if it has expected content indicators
    if expected_content.lower() in page_content.lower():
        print(f"✅ PASSED: {page_name} page has expected content: {expected_content}")
        return True
    else:
        print(f"❌ FAILED: {page_name} page missing expected content: {expected_content}")
        print(f"Content preview: {page_content[:300]}...")
        return False

def main():
    print("🔍 COMPREHENSIVE DASHBOARD PAGE TEST")
    print("=" * 50)
    
    pages_to_test = [
        ('dashboard', 'stats'),
        ('containers', 'docker'),
        ('images', 'docker'),
        ('networks', 'network'),
        ('volumes', 'volume'),
        ('compose', 'compose'),
        ('monitoring', 'monitoring'),
        ('settings', 'setting')
    ]
    
    results = {}
    
    for page_name, expected_content in pages_to_test:
        try:
            results[page_name] = test_page_content(page_name, expected_content)
            time.sleep(0.5)  # Small delay between requests
        except Exception as e:
            print(f"❌ ERROR testing {page_name}: {e}")
            results[page_name] = False
    
    print("\n" + "=" * 50)
    print("📊 TEST SUMMARY")
    print("=" * 50)
    
    passed = sum(1 for result in results.values() if result)
    total = len(results)
    
    for page_name, result in results.items():
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"{status}: {page_name}")
    
    print(f"\n📈 RESULTS: {passed}/{total} pages passed")
    
    if passed == total:
        print("\n🎉 ALL PAGES WORKING WITH REAL CONTENT!")
        print("\n🚀 URL: https://vscode.edunaija.online/proxy/8888/")
        print("👤 Login: <REDACTED> / <REDACTED>")
    else:
        print(f"\n⚠️  {total-passed} pages still showing placeholder content")
        print("\n🔧 SOLUTION NEEDED:")
        print("   - Fix loadContainers(), loadImages(), loadNetworks(), loadVolumes()")
        print("   - Ensure JavaScript properly calls Docker API")
        print("   - Verify real data is displayed, not 'Loading...' text")

if __name__ == "__main__":
    main()